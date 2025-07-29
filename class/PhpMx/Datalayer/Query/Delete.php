<?php

namespace PhpMx\Datalayer\Query;

/** Monta e executa instruções SQL do tipo DELETE com cláusulas WHERE e ORDER BY. */
class Delete extends BaseQuery
{
    protected array $order = [];
    protected array $where = [];

    /** Array de Query para execução */
    function query(): array
    {
        $this->check(['table', 'where']);

        $query = 'DELETE FROM [#table] [#where][#order];';

        $query = prepare($query, [
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
        $fields = is_array($fields) ? $fields : [$fields => $asc];

        foreach ($fields as $fieldName => $orderAsc) {
            if (is_numeric($fieldName)) {
                $fieldName = $orderAsc;
                $orderAsc = $asc;
            }
            $orderAsc = $orderAsc ? 'ASC' : 'DESC';

            $this->order[] = "`$fieldName` $orderAsc";
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

    protected function mountOrder(): string
    {
        if (empty($this->order))
            return '';

        $fields = implode(', ', $this->order);

        return " ORDER BY $fields";
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
}
