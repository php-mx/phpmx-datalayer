<?php

namespace PhpMx\Datalayer\Query;

use PhpMx\Prepare;

class Delete extends BaseQuery
{
    protected array $order = [];
    protected array $where = [];

    /** Array de Query para execução */
    function query(): array
    {
        $this->check(['table', 'where']);

        $query = 'DELETE FROM [#table] [#where][#order];';

        $query = Prepare::prepare($query, [
            'table' => $this->mountTable(),
            'where' => $this->mountWhere(),
            'order'  => $this->mountOrder(),
        ]);

        $values = [];

        foreach ($this->where as $where) {
            if (count($where) > 1 && !is_null($where[1])) {
                array_shift($where);
                foreach ($where as $v) {
                    $values['where_' . count($values)] = $v;
                }
            }
        }

        return [$query, $values];
    }

    /** Executa a query */
    function run(?string $dbName = null): bool
    {
        return parent::run($dbName);
    }

    /** Define a ordem da query */
    function order(string|array $fields, bool $asc = true): static
    {
        $fields = is_array($fields) ? $fields : [$fields];
        foreach ($fields as $field) {
            $this->order[] = $asc ? "$field ASC" : "$field DESC";
        }
        return $this;
    }

    /** Adiciona um WHERE ao select */
    function where(): static
    {
        if (func_num_args()) {
            $this->where[] = func_get_args();
        }
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
        return $this->where("$field in ($ids)");
    }

    /** Adiciona um WHERE para ser utilizado na query verificando se um campo é nulo */
    function whereNull(string $campo, bool $status = true): static
    {
        $this->where($status ? "$campo is null" : "$campo is not null");
        return $this;
    }

    protected function mountOrder(): string
    {
        return empty($this->order) ? '' : ' ORDER BY ' . implode(', ', $this->order);
    }

    protected function mountWhere(): string
    {
        $return     = [];
        $parametros = 0;
        foreach ($this->where as $where) {
            if (count($where) == 1 || is_null($where[1])) {
                $return[] = $where[0];
            } else {
                $igualdade = array_shift($where);
                if (!substr_count($igualdade, ' ') && !substr_count($igualdade, '?')) {
                    $igualdade = "$igualdade = ?";
                }

                foreach ($where as $v) {
                    $igualdade = str_replace(["'?'", '"?"'], '?', $igualdade);
                    $igualdade = preg_replace("/\?/", ":where_" . ($parametros++), $igualdade, 1);
                }
                $return[] = $igualdade;
            }
        }

        $return = array_filter($return);

        return empty($return) ? '' : 'WHERE (' . implode(') AND (', $return) . ')';
    }
}
