<?php

namespace PhpMx\Datalayer\Query;

use Error;

class Insert extends BaseQuery
{
    protected array $columns = [];
    protected array $values = [];

    /** Array de Query para execução */
    function query(): array
    {
        $this->check(['table']);

        $values = [];

        if (empty($this->columns)) {
            $query = 'INSERT INTO [#table] VALUES (null)';
        } else {
            $query = 'INSERT INTO [#table] [#column] VALUES [#values];';
        }

        $query = prepare($query, [
            'table'  => $this->mountTable(),
            'column'  => $this->mountColumn(),
            'values'  => $this->mountValues(),
        ]);
        foreach ($this->values as $pos => $value) {
            foreach ($this->columns as $field) {
                if (isset($value[$field])) {
                    $values[$field . '_' . $pos] = $value[$field];
                }
            }
        }

        return [$query, $values];
    }

    /** Executa a query */
    function run(?string $dbName = null): bool|int
    {
        return parent::run($dbName);
    }

    /** Define os registros para inserção */
    function values(): static
    {
        $this->columns = [];
        $this->values = [];
        foreach (func_get_args() as $register) {
            $insert = [];
            foreach ($register as $field => $value) {
                if (!is_numeric($field)) {
                    $insert[$field] = $value;
                    $this->columns[$field] = true;
                }
            }
            $this->values[] = $insert;
        }
        $this->columns = array_keys($this->columns);

        return $this;
    }

    protected function mountColumn(): string
    {
        $columns = [];
        foreach ($this->columns as $name)
            $columns[] = "`$name`";

        return '(' . implode(', ', $columns) . ')';
    }

    protected function mountValues(): string
    {
        $inserts = [];
        foreach ($this->values as $pos => $value) {
            $insert = [];
            foreach ($this->columns as  $field) {
                if (!array_key_exists($field, $value) || is_null($value[$field])) {
                    $insert[] = 'NULL';
                } else {
                    $insert[] = ':' . $field . '_' . $pos;
                }
            }
            $inserts[] = '(' . implode(', ', $insert) . ')';
        }
        return implode(', ', $inserts);
    }

    protected function mountTable(): string
    {
        if (is_array($this->table))
            throw new Error("Query INSERT can only contain one value for [table]");

        return parent::mountTable();
    }
}
