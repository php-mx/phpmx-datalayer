<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;

class SchemeMap
{
    final const TABLE_MAP = [
        'comment' => null,
        'fields' => [],
        'index' => []
    ];

    final const FIELD_MAP = [
        'type' => 'string',
        'index' => null,
        'default' => null,
        'comment' => '',
        'size' => null,
        'null' => true,
        'config' => []
    ];

    protected array $map;
    protected array $realMap;
    protected string $dbName;

    function __construct(string $dbName)
    {
        $this->dbName = $dbName;
        $this->map = Datalayer::get($this->dbName)->getConfig('__dbMap') ?? [];
        $this->realMap = $this->map;
    }

    /** Retorna o mapa */
    function get(bool $realMap = false): array
    {
        return $realMap ? $this->realMap : $this->map;
    }

    /** Salva as alteraçãos do mapa */
    function save(): void
    {
        Datalayer::get($this->dbName)->setConfig('__dbMap', $this->map);
        $this->realMap = $this->map;
    }

    #==| FIELD |==#

    /** Retorna o mapa de um campo de uma tabela */
    function getField(string $tableName, string $fieldName, bool $inRealMap = false): array
    {
        return $this->getTable($tableName, $inRealMap)['fields'][$fieldName] ?? self::FIELD_MAP;
    }

    /** Adiciona uma campo em uma tabela */
    function addField(string $tableName, string $fieldName, array $fieldMap = []): void
    {
        $this->addTable($tableName);

        $currentFieldMap = $this->getField($tableName, $fieldName);

        $fieldMap['type'] = $fieldMap['type'] ?? $currentFieldMap['type'];
        $fieldMap['comment'] = $fieldMap['comment'] ?? $currentFieldMap['comment'];
        $fieldMap['default'] = $fieldMap['default'] ?? $currentFieldMap['default'];
        $fieldMap['size'] = $fieldMap['size'] ?? $currentFieldMap['size'];
        $fieldMap['null'] = $fieldMap['null'] ?? $currentFieldMap['null'];
        $fieldMap['config'] = $fieldMap['config'] ?? $currentFieldMap['config'];

        $this->map[$tableName]['fields'][$fieldName] = $fieldMap;
    }

    /** Remove uma campo de uma tabela */
    function dropField(string $tableName, string $fieldName): void
    {
        if ($this->checkField($tableName, $fieldName))
            unset($this->map[$tableName]['fields'][$fieldName]);
    }

    /** Verifica se um campo de uma tabela existe */
    function checkField(string $tableName, string $fieldName, bool $inRealMap = false): bool
    {
        return isset($this->getTable($tableName, $inRealMap)['fields'][$fieldName]);
    }

    #==| TABLE |==#

    /** Retorna o mapa de uma tabela */
    function getTable(string $tableName, bool $inRealMap = false): array
    {
        return $this->get($inRealMap)[$tableName] ?? self::TABLE_MAP;
    }

    /** Adiciona uma tabela */
    function addTable(string $tableName, ?string $comment = null): void
    {
        $mapTable = $this->getTable($tableName);

        $mapTable['comment'] = $comment ?? $mapTable['comment'];

        $this->map[$tableName] = $mapTable;
    }

    /** Remove uma tabela */
    function dropTable(string $tableName): void
    {
        if ($this->checkTable($tableName))
            unset($this->map[$tableName]);
    }

    /** Verifica se uma tabela existe */
    function checkTable(string $tableName, bool $inRealMap = false): bool
    {
        return isset($this->get($inRealMap)[$tableName]);
    }

    #==| INDEX |==#

    /** Retorna o nome de um indice de uma tabela */
    function getIndex(string $tableName, string $fieldName, $inRealMap = false): ?string
    {
        return $this->get($inRealMap)[$tableName]['index'][$fieldName] ?? null;
    }

    /** Adiciona um indice de uma tabela */
    function addIndex(string $tableName, string $fieldName): void
    {
        $this->map[$tableName]['index'][$fieldName] = "$tableName.$fieldName";
    }

    /** Remove um indice de uma tabela */
    function dropIndex(string $tableName, string $fieldName): void
    {
        if ($this->checkIndex($tableName, $fieldName))
            unset($this->map[$tableName]['index'][$fieldName]);
    }

    /** Verifica se um indice existe em uma tabela */
    function checkIndex(string $tableName, string $fieldName, $inRealMap = false): bool
    {
        return isset($this->get($inRealMap)[$tableName]['index'][$fieldName]);
    }
}
