<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Scheme\SchemeMap;
use PhpMx\Datalayer\Scheme\SchemeTable;

/** @ignore */
class Scheme
{
    protected SchemeMap $map;

    protected string $dbName;

    /** @var SchemeTable[] */
    protected array $table = [];

    /**
     * Inicializa o Scheme carregando o mapa atual do banco de dados.
     * @param string $dbName Nome do banco de dados.
     */
    function __construct(string $dbName)
    {
        $this->dbName = Datalayer::internalName($dbName);
        $this->map = new SchemeMap($this->dbName);
    }

    /**
     * Retorna ou cria o objeto SchemeTable para a tabela informada.
     * @param string $table Nome da tabela.
     * @param string|null $comment Comentário descritivo da tabela (opcional).
     * @return SchemeTable
     */
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

    /**
     * Calcula o diff entre o esquema declarado e o mapa atual e aplica as queries no banco de dados.
     */
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

    /**
     * Compara os campos declarados com o mapa atual e retorna arrays de campos a adicionar, alterar, remover e reindexar.
     * @param string $tableName Nome da tabela.
     * @param array $alterFields Mapa dos campos declarados na migração.
     * @return array Estrutura com 'add', 'alter', 'drop' e 'index'.
     */
    protected function getAlterTableFields(string $tableName, array $alterFields): array
    {
        $fields = ['add' => [], 'alter' => [], 'drop' => [], 'index' => []];

        foreach ($alterFields as $fieldName => $fieldMap) {
            if ($fieldMap) {
                if ($this->map->checkField($tableName, $fieldName, true)) {
                    if ($this->map->getField($tableName, $fieldName) != $fieldMap) {
                        $fields['alter'][$fieldName] = $fieldMap;

                        $indexName = strToSnakeCase($fieldName);
                        $indexType = $fieldMap['unique'] ? ('unique') : ($fieldMap['index'] ? 'simple' : false);

                        if ($indexType != 'simple' && $this->map->checkIndex($tableName, "simple_$indexName", true))
                            $fields['index']["simple_$indexName"] = false;

                        if ($indexType != 'unique' && $this->map->checkIndex($tableName, "unique_$indexName", true))
                            $fields['index']["unique_$indexName"] = false;

                        if ($indexType && !$this->map->checkIndex($tableName, "{$indexType}_{$indexName}", true))
                            $fields['index']["{$indexType}_{$indexName}"] = [$fieldName, $fieldMap['unique']];
                    }
                } else {
                    $fields['add'][$fieldName] = $fieldMap;

                    $indexName = strToSnakeCase($fieldName);
                    $indexType = $fieldMap['unique'] ? ('unique') : ($fieldMap['index'] ? 'simple' : false);

                    if ($indexType != 'simple' && $this->map->checkIndex($tableName, "simple_$indexName", true))
                        $fields['index']["simple_$indexName"] = false;

                    if ($indexType != 'unique' && $this->map->checkIndex($tableName, "unique_$indexName", true))
                        $fields['index']["unique_$indexName"] = false;

                    if ($indexType && !$this->map->checkIndex($tableName, "{$indexType}_{$indexName}", true))
                        $fields['index']["{$indexType}_{$indexName}"] = [$fieldName, $fieldMap['unique']];
                }
            } else if ($this->map->checkField($tableName, $fieldName, true)) {
                $fields['drop'][$fieldName] = $fieldMap;

                $indexName = strToSnakeCase($fieldName);
            }
        }

        return $fields;
    }

    /**
     * Retorna a lista de tabelas que precisam ser criadas, alteradas ou removidas.
     * @return array Mapa [tableName => alterMap|false].
     */
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
