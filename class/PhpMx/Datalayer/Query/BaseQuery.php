<?php

namespace PhpMx\Datalayer\Query;

use Error;
use PhpMx\Datalayer;

/** Classe base para construção e execução de queries SQL (SELECT, UPDATE, INSERT, DELETE). */
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
        '?',
        'inner',
        'join',
        'left',
        'right',
        'on'
    ];

    function __construct(null|string|array $table = null)
    {
        if ($table)
            $this->table($table);
    }

    /** Array de Query para execução */
    abstract function query(): array;

    /** Verifica se os dados estão completos */
    protected function check(array $dataCheck = []): void
    {
        foreach ($dataCheck as $check)
            if (empty($this->$check))
                throw new Error("Defina um valor de [$check] para a query");
    }

    /** Executa a query */
    function run(?string $dbName = null): mixed
    {
        return Datalayer::get($this->dbName ?? $dbName ?? 'default')->executeQuery($this);
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

    /** Monta a string da tabela com as devidas crases */
    protected function mountTable(): string
    {
        if (!$this->table)
            return '';

        if (is_array($this->table)) {
            $tables = [];
            foreach ($this->table as $name => $alias)
                $tables[] = !is_numeric($name) ? "`$name` as `$alias`" : "`$alias`";
            return implode(', ', $tables);
        }

        if (str_contains($this->table, '.')) {
            $parts = explode('.', $this->table);
            return implode('.', array_map(fn($v) => "`$v`", $parts));
        }

        if (str_contains(trim($this->table), ' ')) {
            $parts = explode(' ', trim($this->table), 2);
            return "`{$parts[0]}` {$parts[1]}";
        }

        return "`$this->table`";
    }
}
