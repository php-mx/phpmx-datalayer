<?php

namespace PhpMx\Datalayer\Connection;

use Exception;
use PDO;
use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Log;

class Sqlite extends BaseConnection
{
    protected string $pdoDriver = 'pdo_sqlite';

    /** Inicializa a conexão */
    protected function load()
    {
        $envName = strtoupper($this->dbName);

        $file = $this->data['file'] ?? env("DB_{$envName}_FILE") ?? $this->dbName;

        if (!str_starts_with($file, '.')) $file = "library/sqlite/$file";

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
            Log::add('datalayer.start', prepare('[#] sqlite', Datalayer::externalName($this->dbName, 'Db')), function () {
                if (!File::check($this->data['file']))
                    Dir::create($this->data['file']);
                $this->instancePDO = new PDO(...(array) $this->instancePDO);
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
                $this->executeQuery('CREATE TABLE `__config` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `name` TEXT NOT NULL UNIQUE, `value` TEXT NOT NULL);');

            foreach ($this->executeQuery(Query::select('__config')) as $config)
                $this->config[$config['name']] = is_serialized($config['value']) ? unserialize($config['value']) : $config['value'];
        }
    }

    /** Query para criação de tabelas */
    protected function schemeQueryCreateTable(string $tableName, ?string $comment, array $fields): array
    {
        $queryFields = [
            '`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT'
        ];

        foreach ($fields['add'] ?? [] as $fielName => $field)
            if ($field)
                $queryFields[] = $this->schemeTemplateField($fielName, $field);

        return [
            prepare("CREATE TABLE `[#name]` ([#fields])", [
                'name' => $tableName,
                'fields' => implode(', ', $queryFields)
            ])
        ];
    }

    /** Query para alteração de tabelas */
    protected function schemeQueryAlterTable(string $tableName, ?string $comment, array $fields): array
    {
        $query = [];

        $listIndexTable = $this->executeQuery("SELECT `name` FROM `sqlite_master` WHERE `tbl_name`='$tableName' and `type` = 'index'");

        $newFields = $this->getConfig('__dbmap')[$tableName]['fields'];

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

        $fieldsName = array_map(fn($v) => "`$v`", $fieldsName);

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
            $query[] = prepare(
                "INSERT INTO `[#table]` ([#fieldsName]) VALUES ([#insert])",
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
            $quotedIndex = "{$name}_{$indexName}";
            if ($scheme) {
                list($field, $unique) = $scheme;
                if ($unique) {
                    $query[] = "CREATE UNIQUE INDEX `$quotedIndex` ON `$name`(`$field`);";
                } else {
                    $query[] = "CREATE INDEX `$quotedIndex` ON `$name`(`$field`);";
                }
            } else {
                $query[] = "DROP INDEX IF EXISTS `$quotedIndex`;";
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
            case 'int':
            case 'boolean':
                $field['type'] = 'INTEGER';
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "`[#name]` [#type][#default][#null]";
                break;

            case 'float':
                $field['type'] = 'REAL';
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "`[#name]` [#type][#default][#null]";
                break;

            case 'string':
            case 'email':
            case 'md5':
            case 'mx5':
            case 'text':
            case 'json':
                $field['type'] = 'VARCHAR';
                $field['default'] = is_null($field['default']) ? '' : " DEFAULT '" . $field['default'] . "'";
                $prepare = "`[#name]` [#type][#default][#null]";
                break;

            default:
                throw new Exception("Type [$field[type]] not suported");
        }

        return prepare($prepare, $field);
    }
}
