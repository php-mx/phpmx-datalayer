<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;

/** @ignore */
class SchemeMap
{
    final const TABLE_MAP = [
        'comment' => null,
        'fields' => [],
        'index' => []
    ];

    final const FIELD_MAP = [
        'type' => 'string',
        'index' => false,
        'unique' => false,
        'default' => null,
        'comment' => '',
        'size' => null,
        'null' => true,
        'settings' => []
    ];

    protected array $map;
    protected array $realMap;
    protected string $dbName;

    function __construct(string $dbName)
    {
        $this->dbName = $dbName;
        $this->map = Datalayer::get($this->dbName)->getConfigGroup('dbmap');
        $this->realMap = $this->map;
    }

    function get(bool $realMap = false): array
    {
        return $realMap ? $this->realMap : $this->map;
    }

    function save(): void
    {
        Datalayer::get($this->dbName)->setConfigGroup('dbmap', $this->map);
        $this->realMap = $this->map;
    }

    function getField(string $tableName, string $fieldName, bool $inRealMap = false): array
    {
        return $this->getTable($tableName, $inRealMap)['fields'][$fieldName] ?? self::FIELD_MAP;
    }

    function addField(string $tableName, string $fieldName, array $fieldMap = []): void
    {
        $this->addTable($tableName);

        $currentFieldMap = $this->getField($tableName, $fieldName);

        $fieldMap['type'] = $fieldMap['type'] ?? $currentFieldMap['type'];
        $fieldMap['comment'] = $fieldMap['comment'] ?? $currentFieldMap['comment'];
        $fieldMap['default'] = $fieldMap['default'] ?? $currentFieldMap['default'];
        $fieldMap['size'] = $fieldMap['size'] ?? $currentFieldMap['size'];
        $fieldMap['null'] = $fieldMap['null'] ?? $currentFieldMap['null'];
        $fieldMap['settings'] = $fieldMap['settings'] ?? $currentFieldMap['settings'];

        $this->map[$tableName]['fields'][$fieldName] = $fieldMap;
    }

    function dropField(string $tableName, string $fieldName): void
    {
        if ($this->checkField($tableName, $fieldName))
            unset($this->map[$tableName]['fields'][$fieldName]);
    }

    function checkField(string $tableName, string $fieldName, bool $inRealMap = false): bool
    {
        return isset($this->getTable($tableName, $inRealMap)['fields'][$fieldName]);
    }

    function getTable(string $tableName, bool $inRealMap = false): array
    {
        return $this->get($inRealMap)[$tableName] ?? self::TABLE_MAP;
    }

    function addTable(string $tableName, ?string $comment = null): void
    {
        $mapTable = $this->getTable($tableName);

        $mapTable['comment'] = $comment ?? $mapTable['comment'];

        $this->map[$tableName] = $mapTable;
    }

    function dropTable(string $tableName): void
    {
        if ($this->checkTable($tableName))
            unset($this->map[$tableName]);
    }

    function checkTable(string $tableName, bool $inRealMap = false): bool
    {
        return isset($this->get($inRealMap)[$tableName]);
    }

    function getIndex(string $tableName, string $indexName, $inRealMap = false): ?array
    {
        return $this->get($inRealMap)[$tableName]['index'][$indexName] ?? null;
    }

    function addIndex(string $tableName, string $indexName, array $index): void
    {
        $this->map[$tableName]['index'][$indexName] = $index;
    }

    function dropIndex(string $tableName, string $indexName): void
    {
        if ($this->checkIndex($tableName, $indexName))
            unset($this->map[$tableName]['index'][$indexName]);
    }

    function checkIndex(string $tableName, string $indexName, $inRealMap = false): bool
    {
        return boolval($this->getIndex($tableName, $indexName, $inRealMap) ?? false);
    }
}
