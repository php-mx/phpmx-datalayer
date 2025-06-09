<?php

namespace PhpMx\Datalayer\Connection;

use PhpMx\Datalayer\Connection;
use PhpMx\Datalayer\Query;
use PhpMx\Dir;
use PhpMx\File;
use Error;
use Exception;
use PDO;
use PDOException;
use PhpMx\Prepare;

class Sqlite extends Connection
{
    /** Inicializa a conexão */
    protected function load()
    {
        $file = $this->data['file'] ?? env(strtoupper("DB_{$this->dbName}_FILE"), $this->dbName);

        $file = File::setEx($file, 'sqlite');

        $this->data['file'] = path('storage/sqlite', $file);

        $this->instancePDO = ["sqlite:" . $this->data['file']];
    }

    /** Retorna a instancia PDO da conexão */
    protected function pdo(): PDO
    {
        if (is_array($this->instancePDO)) {
            try {
                Dir::create($this->data['file']);
                $this->instancePDO = new PDO(...(array) $this->instancePDO);
            } catch (Error | Exception | PDOException $e) {
                throw new Error($e->getMessage());
            }
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
                $this->executeQuery('CREATE TABLE __config (`name` VARCHAR (100), `value` TEXT);');

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

        $indexes = [];

        foreach ($listIndexTable as $index) {
            $index = $index['name'];
            $indexField = str_replace("$tableName.", '', $index);
            $indexes[$indexField] = true;
        }

        $newFields = $this->getConfig('__dbMap')[$tableName]['fields'];

        foreach (array_keys($fields['drop']) as $fieldName) {
            if (isset($newFields[$fieldName]))
                unset($newFields[$fieldName]);
            if (isset($indexes[$fieldName]))
                unset($indexes[$fieldName]);
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

        #$query[] = 'PRAGMA foreign_keys=off';

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

        #$query[] = 'PRAGMA foreign_keys=on';

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

        foreach ($index as $indexName => $indexStatus) {
            if ($indexStatus) {
                $query[] = "CREATE INDEX [$name.$indexName] ON $name($indexName);";
            } else {
                $query[] = "DROP INDEX [$name.$indexName];";
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
