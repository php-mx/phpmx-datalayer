<?php

namespace PhpMx\Datalayer\Connection;

use Error;
use Exception;
use PDO;
use PDOException;
use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Prepare;

class Sqlite extends BaseConnection
{
    /** Inicializa a conexão */
    protected function load()
    {
        $envName = strtoupper($this->dbName);

        $file = $this->data['file'] ?? env("DB_{$envName}_FILE") ?? $this->dbName;

        if (!str_starts_with($file, '.')) $file = "storage/sqlite/$file";

        $file = trim($file, '.');
        $file = path($file);

        $path = explode('/', $file);
        $file = array_pop($path);
        $path = path(...$path);

        $ex = File::getEx($file);
        $ex = match ($ex) {
            'sqlite', 'sqlite3', 'db', 'db3', 'sl3', 's3db', => $ex,
            default => null,
        };

        $file = $ex ? substr($file, 0, strlen($ex) * -1) : $file;
        $file = strToCamelCase($file);
        $file = File::setEx($file, $ex ?? 'sqlite');
        $file = path($path, $file);

        $this->data['file'] = path($file);

        $this->instancePDO = ["sqlite:" . $this->data['file']];
    }

    /** Retorna a instancia PDO da conexão */
    protected function &pdo(): PDO
    {
        if (is_array($this->instancePDO)) {
            log_add('datalayer.start', '[#] sqlite', [Datalayer::externalName($this->dbName, 'Db')], function () {
                try {
                    if (!File::check($this->data['file'])) Dir::create($this->data['file']);
                    $this->instancePDO = new PDO(...(array) $this->instancePDO);
                } catch (Error | Exception | PDOException $e) {
                    throw new Exception($e->getMessage());
                }
            });
        }
        return $this->instancePDO;
    }

    /** Carrega as configurações do banco armazenadas na tabela __config */
    protected function loadConfig(): void
    {
        if (!$this->config) {
            $this->config = [];

            $configTableExistsQuery =  Query::select('sqlite_master')
                ->where('type', 'table')
                ->where('name', '__config')
                ->limit(1);

            if (!count($this->executeQuery($configTableExistsQuery)))
                $this->executeQuery('CREATE TABLE __config (name TEXT PRIMARY KEY, value TEXT);');

            foreach ($this->executeQuery(Query::select('__config')) as $config)
                $this->config[$config['name']] = is_serialized($config['value']) ? unserialize($config['value']) : $config['value'];
        }
    }

    /** Query para criação de tabelas */
    protected function schemeQueryCreateTable(string $tableName, ?string $comment, array $fields): array
    {
        $queryFields = [
            '[id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'
        ];

        foreach ($fields['add'] ?? [] as $fielName => $field)
            if ($field)
                $queryFields[] = $this->schemeTemplateField($fielName, $field);

        return [
            Prepare::prepare("CREATE TABLE [[#name]] ([#fields])", [
                'name' => $tableName,
                'fields' => implode(', ', $queryFields)
            ])
        ];
    }

    /** Query para alteração de tabelas */
    protected function schemeQueryAlterTable(string $tableName, ?string $comment, array $fields): array
    {
        $query = [];

        $listIndexTable = $this->executeQuery("SELECT name FROM sqlite_master WHERE tbl_name='$tableName' and  type = 'index'");

        $newFields = $this->getConfig('__dbMap')[$tableName]['fields'];

        foreach (array_keys($fields['drop']) as $fieldName) {
            if (isset($newFields[$fieldName]))
                unset($newFields[$fieldName]);
            if (isset($indexes[$fieldName]))
                unset($indexes[$fieldName]);
        }

        $indexes = [];

        foreach ($listIndexTable as $index) {
            $index = $index['name'];
            $index = explode('.', $index);
            list($indexTable, $indexField, $indexType) = $index;
            if ($indexes[$indexField])
                $indexes["$indexField.$indexType"] = [
                    $indexField,
                    boolval($indexType == 'unique')
                ];
        }

        foreach ($fields['add'] as $name => $field) {
            if (is_null($field['default'])) {
                $field['null'] = true;
            }
            $newFields[$name] = $field;
        }

        foreach ($fields['alter'] as $name => $field) {
            $newFields[$name] = $field;
        }

        $fieldsName = ['id'];

        array_push($fieldsName, ...array_keys($newFields));

        $fieldsName = implode(', ', $fieldsName);

        $insert = [];

        foreach ($this->executeQuery(Query::select($tableName)) as $result) {
            $innerValues = [$result['id']];
            foreach ($newFields as $fieldName => $fieldData) {
                $inner = $result[$fieldName] ?? $fieldData['default'] ?? 'NULL';
                $inner = is_int($inner) || $inner == 'NULL' ? $inner : "'$inner'";
                $innerValues[] = $inner;
            }
            $insert[] = implode(', ', $innerValues);
        }

        array_push($query, ...$this->schemeQueryDropTable($tableName));
        array_push($query, ...$this->schemeQueryCreateTable($tableName, $comment, ['add' => $newFields]));

        if (count($insert)) {
            $query[] = Prepare::prepare(
                "INSERT INTO [[#table]] ([#fieldsName]) VALUES ([#insert])",
                [
                    'table' => $tableName,
                    'fieldsName' => $fieldsName,
                    'insert' => implode('), (', $insert)
                ]
            );
        }

        array_push($query, ...$this->schemeQueryUpdateTableIndex($tableName, $indexes));

        return $query;
    }

    /** Query para remoção de tabelas */
    protected function schemeQueryDropTable(string $tableName): array
    {
        return ["DROP TABLE `$tableName`"];
    }

    /** Query para atualização de index */
    protected function schemeQueryUpdateTableIndex(string $name, array $index): array
    {
        $query = [];

        foreach ($index as $indexName => $scheme) {
            if ($scheme) {
                list($field, $unique) = $scheme;
                if ($unique) {
                    $query[] = "CREATE UNIQUE INDEX [$name.$indexName] ON $name($field);";
                } else {
                    $query[] = "CREATE INDEX [$name.$indexName] ON $name($field);";
                }
            } else {
                $query[] = "DROP INDEX IF EXISTS [$name.$indexName];";
            }
        }

        return $query;
    }

    /** Retorna o template do campo para composição de querys */
    protected static function schemeTemplateField(string $fieldName, array $field): string
    {
        $prepare = '';
        $field['name'] = $fieldName;
        $field['null'] = $field['null'] ? '' : ' NOT NULL';
        switch ($field['type']) {
            case 'idx':
            case 'time':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "[[#name]] int([#size]) [#default][#null]";
                break;

            case 'int':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "[[#name]] int([#size])[#default][#null]";
                break;

            case 'boolean':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "[[#name]] tinyint([#size])[#default][#null]";
                break;

            case 'float':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "[[#name]] float([#size])[#default][#null]";
                break;

            case 'ids':
            case 'log':
            case 'text':
            case 'json':
            case 'config':
                $field['default'] = is_null($field['default']) ? '' : " DEFAULT '" . $field['default'] . "'";
                $prepare = "[[#name]] text[#default][#null]";
                break;

            case 'string':
            case 'email':
            case 'hash':
            case 'code':
                $field['default'] = is_null($field['default']) ? '' : " DEFAULT '" . $field['default'] . "'";
                $prepare = "[[#name]] varchar([#size])[#default][#null]";
                break;
        }
        return Prepare::prepare($prepare, $field);
    }
}
