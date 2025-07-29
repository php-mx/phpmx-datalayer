<?php

namespace PhpMx\Datalayer\Query;

use Error;

/** Monta e executa instruções SQL do tipo UPDATE com suporte a where, whereIn e whereNull. */
class Update extends BaseQuery
{
    protected array $values = [];
    protected $where = [];

    /** Array de Query para execução */
    function query(): array
    {
        $this->check(['table', 'where', 'values']);

        $query = 'UPDATE [#table] SET [#values] [#where];';

        $query = prepare($query, [
            'table'   => $this->mountTable(),
            'values' => $this->mountValues(),
            'where'   => $this->mountWhere(),
        ]);

        $values = [];
        $count  = 0;

        foreach ($this->where as $where) {
            if (count($where) > 1 && !is_null($where[1])) {
                array_shift($where);
                foreach ($where as $v) {
                    $values['where_' . ($count++)] = $v;
                }
            }
        }

        foreach ($this->values as $name => $value) {
            if (!is_numeric($name) && !is_null($value)) {
                $values["value_$name"] = $value;
            }
        }

        return [$query, $values];
    }

    /** Executa a query */
    function run(?string $dbName = null): bool
    {
        return parent::run($dbName);
    }

    /** Define os campos que devem ser alterados com base em um array */
    function values(array $array): static
    {
        foreach ($array as $field => $value) {
            $this->values[$field] = $value;
        }

        return $this;
    }

    /** Adiciona um WHERE ao select */
    function where(): static
    {
        if (func_num_args())
            $this->where[] = func_get_args();

        return $this;
    }

    /** Adiciona um WHERE verificando valores numericos em um array */
    function whereIn(string $field, array|string $ids): static
    {
        if (is_string($ids))
            $ids = explode(',', $ids);

        $ids = array_filter($ids, fn($id) => is_int($id));

        if (!count($ids))
            return $this->where('false');

        $ids = implode(',', $ids);
        return $this->where("`$field` in ($ids)");
    }

    /** Adiciona um WHERE para ser utilizado na query verificando se um campo é nulo */
    function whereNull(string $campo, bool $status = true): static
    {
        $this->where($status ? "`$campo` is null" : "`$campo` is not null");
        return $this;
    }

    protected function mountValues(): string
    {
        $change = [];
        foreach ($this->values as $name => $value) {
            if (is_numeric($name)) {
                $change[] = "`$value` = NULL";
            } else if (is_null($value)) {
                $change[] =  "`$name` = NULL";
            } else {
                $fname = $name;
                $change[] = "`$fname` = :value_$name";
            }
        }
        return implode(', ', $change);
    }

    protected function mountWhere(): string
    {
        $return     = [];
        $parametros = 0;
        foreach ($this->where as $where) {
            if (count($where) == 1 || is_null($where[1])) {
                $return[] = $where[0];
            } else {
                $expression = array_shift($where);
                if (!substr_count($expression, ' ') && !substr_count($expression, '?'))
                    $expression = "$expression = ?";

                $expression = preg_replace_callback('/\b([a-z_][a-z0-9_]*)\b/i', function ($match) {
                    $token = strtolower($match[1]);
                    return in_array($token, $this->sqlKeywords) ? $match[0] : "`{$match[1]}`";
                }, $expression);

                $expression = str_replace_all(["'?'", '"?"'], '?', $expression);

                foreach ($where as $v)
                    $expression = str_replace_first('?', ":where_" . ($parametros++), $expression);

                $return[] = $expression;
            }
        }

        $return = array_filter($return);

        return empty($return) ? '' : 'WHERE (' . implode(') AND (', $return) . ')';
    }

    protected function mountTable(): string
    {
        if (is_array($this->table))
            throw new Error("Query UPDATE can only contain one value for [table]");

        return parent::mountTable();
    }
}
