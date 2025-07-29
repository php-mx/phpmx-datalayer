<?php

namespace PhpMx\Datalayer\Query;

/** Monta e executa instruções SQL do tipo SELECT com suporte a fields, where, order, group e paginação. */
class Select extends BaseQuery
{
    protected array $fields = [];
    protected int|string $limit = 0;
    protected array $order = [];
    protected string $group = '';
    protected array $where = [];

    /** Array de Query para execução */
    function query(): array
    {
        $this->check(['table']);

        $query = 'SELECT [#fields] FROM [#table] [#where][#group][#order][#limit];';

        $query = prepare($query, [
            'fields' => $this->mountFields(),
            'table'  => $this->mountTable(),
            'where'  => $this->mountWhere(),
            'limit'  => $this->mountLimit(),
            'order'  => $this->mountOrder(),
            'group'  => $this->mountGroup(),
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
    function run(?string $dbName = null): bool|array
    {
        return parent::run($dbName);
    }

    /** Define os campos que devem ser retornados no select, NULL ou * retorna todos os campos */
    function fields(null|string|array $fields): static
    {
        if (is_null($fields) || $fields == '*') {
            $this->fields = [];
        } else if (func_num_args() > 1) {
            foreach (func_get_args() as $field) {
                $this->fields($field);
            }
        } else {
            $fields = is_array($fields) ? $fields : [$fields];
            foreach ($fields as $name => $value) {
                if (is_numeric($name)) {
                    $this->fields[$value] = null;
                } else {
                    $this->fields[$name] = $value;
                }
            }
        }
        return $this;
    }

    /** Define a quantidade maxima de valores removidos */
    function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /** Define uma paginação para o select */
    function page(int $page, int $limit): static
    {
        $page = $page ? $limit * $page : 0;
        $this->limit = "$limit OFFSET $page";
        return $this;
    }

    /** Define um agrupamento para a query */
    function group(string $field): static
    {
        $field = explode('.', $field);
        $field = array_map(fn($v) => $v, $field);
        $field = implode('.', $field);

        $this->group = $field;
        return $this;
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

    /** Define a ordem especifica da query */
    function orderField(string $field, array $orderValues): static
    {
        if (!count($orderValues))
            return $this;

        $field = explode('.', $field);
        $field = array_map(fn($name) => "`$name`", $field);
        $field = implode('.', $field);

        $order = "CASE $field";

        $orderValues = array_reverse($orderValues);

        foreach ($orderValues as $pos => $val) {
            if (is_string($val)) {
                $val = addslashes($val);
                $val = "'$val'";
            }
            $order .= " WHEN $val THEN -" . $pos + 1;
        }

        $order .= " ELSE 0 END";

        $this->order[] = $order;

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

    protected function mountFields(): string
    {
        $fields = [];
        foreach ($this->fields as $name => $alias) {
            if (!is_numeric($name)) {
                if (!substr_count($name, '(')) {
                    if (substr_count($name, '.')) {
                        $name = explode('.', $name);
                        $name = array_map(fn($v) => $v != '*' ? $v : $v, $name);
                        $name = implode('.', $name);
                    }
                }
                $fields[] = $alias ? "$name as $alias" : $name;
            }
        }
        return empty($fields) ? '*' : implode(', ', $fields);
    }

    protected function mountLimit(): string
    {
        return $this->limit ? " LIMIT $this->limit" : '';
    }

    protected function mountOrder(): string
    {
        if (empty($this->order))
            return '';

        $fields = implode(', ', $this->order);

        return " ORDER BY $fields";
    }

    protected function mountGroup(): string
    {
        return empty($this->group) ? '' : ' GROUP BY ' . $this->group;
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
