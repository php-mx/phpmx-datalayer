<?php

namespace PhpMx\Datalayer\Query;

use PhpMx\Prepare;

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

        $query = Prepare::prepare($query, [
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
        $this->limit = "$page, $limit";
        return $this;
    }

    /** Define um agrupamento para a query */
    function group(string $field)
    {
        $field = explode('.', $field);
        $field = array_map(fn($v) => "`$v`", $field);
        $field = implode('.', $field);

        $this->group = $field;
        return $this;
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

    /** Define a ordem especifica da query */
    function orderField(string $field, array $orderValues): static
    {
        if (!count($orderValues))
            return $this;

        $field = explode('.', $field);
        $field = array_map(fn($v) => "`$v`", $field);
        $field = implode('.', $field);

        $order = "CASE $field ";

        foreach ($orderValues as $pos => $id)
            $order .= " WHEN $id THEN $pos ";

        $order .= 'ELSE 9999 END';

        $this->order[] = $order;

        return $this;
    }

    /** Adiciona um WHERE ao select */
    function where(...$args): static
    {
        if (count($args) == 2) {
            if (is_array($args[1]))
                return $this->whereIn(...$args);
            if (is_bool($args[1])) {
                $compare = $args[1] ? ' != ?' : ' = ?';
                return $this->where("$args[0] $compare", 0);
            }
        }

        if (count($args))
            $this->where[] = $args;

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
        $campo = substr_count($campo, '(') ? $campo : "`$campo`";
        $this->where($status ? "$campo is null" : "$campo is not null");
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
                        $name = array_map(fn($v) => $v != '*' ? "`$v`" : $v, $name);
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
        return empty($this->order) ? '' : ' ORDER BY ' . implode(', ', $this->order);
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
