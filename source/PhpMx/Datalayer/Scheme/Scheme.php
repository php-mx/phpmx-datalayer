<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Scheme\SchemeMap;
use PhpMx\Datalayer\Scheme\SchemeTable;

class Scheme
{
    protected SchemeMap $map;

    protected string $dbName;

    /** @var SchemeTable[] */
    protected array $table = [];

    function __construct(string $dbName)
    {
        $this->dbName = Datalayer::internalName($dbName);
        $this->map = new SchemeMap($this->dbName);
    }

    /** Retorna o objeto de uma tabela */
    function &table(string $table, ?string $comment = null): SchemeTable
    {
        $table = Datalayer::internalName($table);

        if (!isset($this->table[$table])) {
            $this->table[$table] = new SchemeTable(
                $table,
                ['comment' => $comment ?? null],
                $this->map->getTable($table)
            );
        }
        return $this->table[$table];
    }

    /** Aplica as alterações no banco de dados */
    function apply(): void
    {
        $listTable = $this->getAlterListTable();

        $schemeQueryList = [];

        foreach ($listTable as $tableName => $tableMap) {
            if ($tableMap) {
                $this->map->addTable($tableName, $tableMap['comment'] ?? null);

                $fields = $this->getAlterTableFields($tableName, $tableMap['fields']);

                foreach ($fields['add'] as $fieldName => $fieldMap) {
                    $this->map->addField($tableName, $fieldName, $fieldMap);
                }

                foreach ($fields['alter'] as $fieldName => $fieldMap) {
                    $this->map->addField($tableName, $fieldName, $fieldMap);
                }

                foreach ($fields['drop'] as $fieldName => $fieldMap)
                    $this->map->dropField($tableName, $fieldName);

                foreach ($fields['index'] as $indexName => $index) {
                    if ($index) {
                        $this->map->addIndex($tableName, $indexName, $index);
                    } else {
                        $this->map->dropIndex($tableName, $indexName);
                    }
                }

                if ($this->map->checkTable($tableName, true)) {
                    $schemeQueryList[] = ['alter', [$tableName, $tableMap['comment'], $fields]];
                } else {
                    $schemeQueryList[] = ['create', [$tableName, $tableMap['comment'], $fields]];
                }
                $schemeQueryList[] = ['index', [$tableName, $fields['index']]];
            } else {
                $this->map->dropTable($tableName);
                $schemeQueryList[] = ['drop', [$tableName]];
            }
        }

        Datalayer::get($this->dbName)->executeSchemeQuery($schemeQueryList);

        $this->map->save();
    }

    /** Retorna o array de campos que devem ser adicionados, alterados ou removidos de uma tabela */
    protected function getAlterTableFields(string $tableName, array $alterFields): array
    {
        $fields = ['add' => [], 'alter' => [], 'drop' => [], 'index' => []];

        foreach ($alterFields as $fieldName => $fieldMap) {
            if ($fieldMap) {
                if ($this->map->checkField($tableName, $fieldName, true)) {
                    if ($this->map->getField($tableName, $fieldName) != $fieldMap) {
                        $fields['alter'][$fieldName] = $fieldMap;

                        $indexType = $fieldMap['unique'] ? ('unique') : ($fieldMap['index'] ? 'simple' : false);

                        if ($indexType == 'unique' && $this->map->checkIndex($tableName, "$fieldName.simple", true))
                            $fields['index']["$fieldName.simple"] = false;

                        if ($indexType == 'simple' && $this->map->checkIndex($tableName, "$fieldName.unique", true))
                            $fields['index']["$fieldName.unique"] = false;

                        if ($indexType)
                            $fields['index']["$fieldName.$indexType"] = [$fieldName, $fieldMap['unique']];
                    }
                } else {
                    $fields['add'][$fieldName] = $fieldMap;

                    $indexType = $fieldMap['unique'] ? ('unique') : ($fieldMap['index'] ? 'simple' : false);

                    if ($indexType == 'unique' && $this->map->checkIndex($tableName, "$fieldName.simple", true))
                        $fields['index']["$fieldName.simple"] = false;

                    if ($indexType == 'simple' && $this->map->checkIndex($tableName, "$fieldName.unique", true))
                        $fields['index']["$fieldName.unique"] = false;

                    if ($indexType)
                        $fields['index']["$fieldName.$indexType"] = [$fieldName, $fieldMap['unique']];
                }
            } else if ($this->map->checkField($tableName, $fieldName, true)) {
                $fields['drop'][$fieldName] = $fieldMap;

                if ($this->map->checkIndex($tableName, "$fieldName.simple", true))
                    $fields['index']["$fieldName.simple"] = false;

                if ($this->map->checkIndex($tableName, "$fieldName.unique", true))
                    $fields['index']["$fieldName.unique"] = false;
            }
        }

        return $fields;
    }

    /** Retorna a lista de tableas que devem ser alteradas */
    protected function getAlterListTable(): array
    {
        $listTable = [];
        foreach ($this->table as $tableName => $tableObject) {
            $table = $tableObject->getTableAlterMap();
            if ($table || $this->map->checkTable($tableName)) {
                $listTable[$tableName] = $table;
            }
        }
        return $listTable;
    }
}
