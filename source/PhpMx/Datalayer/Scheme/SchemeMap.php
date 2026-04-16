<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;

/**
 * Gerencia o mapa de esquema persistido no banco de dados (tabela __config, grupo dbmap).
 * Serve como fonte de verdade para comparação de diffs entre o esquema declarado e o estado atual.
 * @ignore
 */
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

    /**
     * Carrega o mapa de esquema do banco de dados.
     * @param string $dbName Nome do banco de dados.
     */
    function __construct(string $dbName)
    {
        $this->dbName = $dbName;
        $this->map = Datalayer::get($this->dbName)->getConfigGroup('dbmap');
        $this->realMap = $this->map;
    }

    /**
     * Retorna o mapa de esquema atual (em memória) ou o mapa real (persistido no banco).
     * @param bool $realMap Se verdadeiro retorna o mapa real (antes de modificações pendentes).
     * @return array
     */
    function get(bool $realMap = false): array
    {
        return $realMap ? $this->realMap : $this->map;
    }

    /**
     * Persiste o mapa de esquema atual no banco de dados e atualiza o mapa real.
     */
    function save(): void
    {
        Datalayer::get($this->dbName)->setConfigGroup('dbmap', $this->map);
        $this->realMap = $this->map;
    }

    /**
     * Retorna o mapa de um campo específico de uma tabela.
     * @param string $tableName Nome da tabela.
     * @param string $fieldName Nome do campo.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return array
     */
    function getField(string $tableName, string $fieldName, bool $inRealMap = false): array
    {
        return $this->getTable($tableName, $inRealMap)['fields'][$fieldName] ?? self::FIELD_MAP;
    }

    /**
     * Adiciona ou atualiza o mapa de um campo na tabela.
     * @param string $tableName Nome da tabela.
     * @param string $fieldName Nome do campo.
     * @param array $fieldMap Mapa do campo a armazenar.
     */
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

    /**
     * Remove o mapa de um campo da tabela.
     * @param string $tableName Nome da tabela.
     * @param string $fieldName Nome do campo a remover.
     */
    function dropField(string $tableName, string $fieldName): void
    {
        if ($this->checkField($tableName, $fieldName))
            unset($this->map[$tableName]['fields'][$fieldName]);
    }

    /**
     * Verifica se um campo existe no mapa da tabela.
     * @param string $tableName Nome da tabela.
     * @param string $fieldName Nome do campo.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return bool
     */
    function checkField(string $tableName, string $fieldName, bool $inRealMap = false): bool
    {
        return isset($this->getTable($tableName, $inRealMap)['fields'][$fieldName]);
    }

    /**
     * Retorna o mapa de uma tabela específica.
     * @param string $tableName Nome da tabela.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return array
     */
    function getTable(string $tableName, bool $inRealMap = false): array
    {
        return $this->get($inRealMap)[$tableName] ?? self::TABLE_MAP;
    }

    /**
     * Adiciona ou atualiza o registro de uma tabela no mapa.
     * @param string $tableName Nome da tabela.
     * @param string|null $comment Comentário descritivo da tabela (opcional).
     */
    function addTable(string $tableName, ?string $comment = null): void
    {
        $mapTable = $this->getTable($tableName);

        $mapTable['comment'] = $comment ?? $mapTable['comment'];

        $this->map[$tableName] = $mapTable;
    }

    /**
     * Remove o registro de uma tabela do mapa.
     * @param string $tableName Nome da tabela a remover.
     */
    function dropTable(string $tableName): void
    {
        if ($this->checkTable($tableName))
            unset($this->map[$tableName]);
    }

    /**
     * Verifica se uma tabela existe no mapa.
     * @param string $tableName Nome da tabela.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return bool
     */
    function checkTable(string $tableName, bool $inRealMap = false): bool
    {
        return isset($this->get($inRealMap)[$tableName]);
    }

    /**
     * Retorna os dados de um índice de uma tabela.
     * @param string $tableName Nome da tabela.
     * @param string $indexName Nome do índice.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return array|null
     */
    function getIndex(string $tableName, string $indexName, $inRealMap = false): ?array
    {
        return $this->get($inRealMap)[$tableName]['index'][$indexName] ?? null;
    }

    /**
     * Registra um índice no mapa da tabela.
     * @param string $tableName Nome da tabela.
     * @param string $indexName Nome do índice.
     * @param array $index Dados do índice [campo, unique].
     */
    function addIndex(string $tableName, string $indexName, array $index): void
    {
        $this->map[$tableName]['index'][$indexName] = $index;
    }

    /**
     * Remove um índice do mapa da tabela.
     * @param string $tableName Nome da tabela.
     * @param string $indexName Nome do índice a remover.
     */
    function dropIndex(string $tableName, string $indexName): void
    {
        if ($this->checkIndex($tableName, $indexName))
            unset($this->map[$tableName]['index'][$indexName]);
    }

    /**
     * Verifica se um índice existe no mapa da tabela.
     * @param string $tableName Nome da tabela.
     * @param string $indexName Nome do índice.
     * @param bool $inRealMap Se verdadeiro consulta o mapa real.
     * @return bool
     */
    function checkIndex(string $tableName, string $indexName, $inRealMap = false): bool
    {
        return boolval($this->getIndex($tableName, $indexName, $inRealMap) ?? false);
    }
}
