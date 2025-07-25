<?php

namespace PhpMx\Datalayer\Query;

use Error;
use PhpMx\Datalayer;

abstract class BaseQuery
{
    protected array $data = [];

    protected ?string $dbName = null;

    protected null|string|array $table = null;

    protected $sqlKeywords = [
        'select',
        'from',
        'where',
        'and',
        'or',
        'not',
        'in',
        'is',
        'null',
        'like',
        'between',
        'exists',
        'true',
        'false',
        'as',
        '?'
    ];

    function __construct(null|string|array $table)
    {
        $this->table($table);
    }

    /** Array de Query para execução */
    abstract function query(): array;

    /** Verifica se os dados estão completos */
    protected function check(array $dataCheck = []): void
    {
        foreach ($dataCheck as $check)
            if (empty($this->$check))
                throw new Error("Define um valor de [$check] para a query");
    }

    /** Executa a query */
    function run(?string $dbName = null): mixed
    {
        return Datalayer::get($this->dbName ?? $dbName)->executeQuery($this);
    }

    /** Define o banco de dados que deve receber a query */
    function dbName(?string $dbName): static
    {
        $this->dbName = $dbName;
        return $this;
    }

    /** Define uma tabela para ser utilizada na query */
    function table(null|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    protected function mountTable(): string
    {
        if ($this->table) {
            if (is_array($this->table)) {
                $table = [];
                foreach ($this->table as $name => $alias)
                    $table[] = !is_numeric($name) ? "`$name` as `$alias`" : "`$alias`";
                return implode(', ', $table);
            } elseif (substr_count($this->table, '.')) {
                $table = explode('.', $this->table);
                foreach ($table as &$name)
                    $name = "`$name`";
                return implode('.', $table);
            } else {
                return "`$this->table`";
            }
        }
        return '';
    }
}
