<?php

namespace PhpMx\Datalayer\Query;

/**
 * Monta e executa instruções SQL do tipo SELECT com suporte a fields, where, order, group, joins e paginação.
 */
class Select extends BaseQuery
{
    protected array $fields = [];
    protected int|string $limit = 0;
    protected array $order = [];
    protected string $group = '';
    protected array $where = [];
    protected array $joins = [];
    protected bool $distinct = false;

    /**
     * Retorna o array de dados necessários para execução da query SELECT.
     * @return array
     */
    function query(): array
    {
        $this->check(['table']);

        $query = 'SELECT [#fields] FROM [#table][#joins] [#where][#group][#order][#limit];';

        $query = prepare($query, [
            'fields' => $this->mountFields(),
            'table' => $this->mountTable(),
            'joins' => $this->mountJoins(),
            'where' => $this->mountWhere(),
            'limit' => $this->mountLimit(),
            'order' => $this->mountOrder(),
            'group' => $this->mountGroup(),
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

    /**
     * Executa a query SELECT e retorna os registros encontrados.
     * @param string|null $dbName Nome do banco de dados (opcional, usa 'main' por padrão).
     * @return bool|array
     */
    function run(?string $dbName = null): bool|array
    {
        return parent::run($dbName);
    }

    /**
     * Executa um COUNT e retorna o total de registros correspondentes à query.
     * @return int
     */
    function count(): int
    {
        $oldFields = $this->fields;
        $oldLimit = $this->limit;
        $oldGroup = $this->group;
        $oldOrder = $this->order;

        $this->fields = ["COUNT(*)" => 'total'];
        $this->limit = 0;
        $this->group = '';
        $this->order = [];

        $hashCache = md5(serialize($this->query()));

        $total = cacheTime($hashCache, 60, fn() => $this->run()[0]['total']);

        $this->fields = $oldFields;
        $this->limit = $oldLimit;
        $this->group = $oldGroup;
        $this->order = $oldOrder;

        return (int) $total;
    }

    /**
     * Define se o SELECT deve usar DISTINCT para evitar registros duplicados.
     * @param bool $distinct Se verdadeiro aplica DISTINCT.
     * @return static
     */
    function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Define os campos a serem retornados no SELECT.
     * @param string|array|null $fields Campo, array [campo => alias] ou null para retornar todos.
     * @return static
     */
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

    /**
     * Define o limite máximo de registros retornados.
     * @param int $limit Número máximo de registros.
     * @return static
     */
    function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Define a paginação da query com limite e offset calculado pela página.
     * @param int $page Número da página (mínimo 1).
     * @param int $limit Quantidade de registros por página.
     * @return static
     */
    function page(int $page, int $limit): static
    {
        $page = max(1, $page);
        $offset = $limit * ($page - 1);
        $this->limit = "$limit OFFSET $offset";
        return $this;
    }

    /**
     * Define o agrupamento dos resultados da query.
     * @param string $field Campo a ser usado no GROUP BY.
     * @return static
     */
    function group(string $field): static
    {
        $field = explode('.', $field);
        $field = array_map(fn($v) => $v, $field);
        $field = implode('.', $field);

        $this->group = $field;
        return $this;
    }

    /**
     * Define a ordenação dos resultados da query.
     * @param string|array $fields Campo ou array associativo [campo => asc].
     * @param bool $asc Se verdadeiro ordena de forma crescente (padrão).
     * @return static
     */
    function order(string|array $fields, bool $asc = true): static
    {
        $fields = is_array($fields) ? $fields : [$fields => $asc];

        foreach ($fields as $fieldName => $orderAsc) {
            if (is_numeric($fieldName)) {
                $fieldName = $orderAsc;
                $orderAsc = $asc;
            }
            $orderAsc = $orderAsc ? 'ASC' : 'DESC';

            if (str_contains($fieldName, '.')) {
                $parts = explode('.', $fieldName);
                $fieldName = implode('.', array_map(fn($v) => "`$v`", $parts));
            } else {
                $fieldName = "`$fieldName`";
            }

            $this->order[] = "$fieldName $orderAsc";
        }
        return $this;
    }

    /**
     * Define a ordenação por uma lista específica de valores de um campo.
     * @param string $field Nome do campo a ordenar.
     * @param array $orderValues Lista de valores na ordem desejada.
     * @return static
     */
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

    /**
     * Adiciona uma cláusula WHERE à query.
     * @param string $expression Expressão da condição.
     * @param mixed ...$values Valores a substituir os placeholders '?' da expressão.
     * @return static
     */
    function where(): static
    {
        if (func_num_args())
            $this->where[] = func_get_args();

        return $this;
    }
    /**
     * Adiciona uma cláusula WHERE verificando se um campo está contido em uma lista de IDs inteiros.
     * @param string $field Nome do campo.
     * @param array|string $ids Lista de IDs ou string separada por vírgulas.
     * @return static
     */
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

    /**
     * Adiciona uma cláusula WHERE verificando se um campo é nulo ou não.
     * @param string $campo Nome do campo.
     * @param bool $status Se verdadeiro verifica IS NULL, se falso verifica IS NOT NULL.
     * @return static
     */
    function whereNull(string $campo, bool $status = true): static
    {
        $this->where($status ? "$campo is null" : "$campo is not null");
        return $this;
    }

    /**
     * Adiciona um JOIN à query.
     * @param string $table Nome da tabela a unir.
     * @param string $condition Condição do JOIN.
     * @param string $type Tipo do JOIN (INNER, LEFT, RIGHT).
     * @return static
     */
    function join(string $table, string $condition, string $type = 'INNER'): static
    {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'condition' => $condition
        ];
        return $this;
    }

    /**
     * Atalho para adicionar um LEFT JOIN à query.
     * @param string $table Nome da tabela a unir.
     * @param string $condition Condição do JOIN.
     * @return static
     */
    function leftJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'LEFT');
    }

    /**
     * Atalho para adicionar um RIGHT JOIN à query.
     * @param string $table Nome da tabela a unir.
     * @param string $condition Condição do JOIN.
     * @return static
     */
    function rightJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    /**
     * Atalho para adicionar um INNER JOIN à query.
     * @param string $table Nome da tabela a unir.
     * @param string $condition Condição do JOIN.
     * @return static
     */
    function innerJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'INNER');
    }

    protected function mountFields(): string
    {
        $fields = [];
        foreach ($this->fields as $name => $alias) {
            if (!is_numeric($name)) {
                if (str_contains(strtolower($name), ' as ')) {
                    $parts = preg_split('/ as /i', $name);
                    $name = trim($parts[0]);
                    $alias = trim($parts[1]);
                }

                if (!str_contains($name, '(')) {
                    if (str_contains($name, '.')) {
                        $parts = explode('.', $name);
                        $parts = array_map(fn($v) => $v != '*' ? "`$v`" : $v, $parts);
                        $name = implode('.', $parts);
                    } else {
                        $name = "`$name`";
                    }
                }

                $fields[] = $alias ? "$name as `$alias`" : $name;
            }
        }

        if (empty($fields)) {
            $mainTable = is_array($this->table) ? array_key_first($this->table) : $this->table;
            if (!empty($mainTable) && is_string($mainTable)) {
                $pureTable = explode(' ', trim($mainTable))[0];
                $fieldsStr = "`$pureTable`.*";
            } else {
                $fieldsStr = '*';
            }
        } else {
            $fieldsStr = implode(', ', $fields);
        }

        return $this->distinct ? "DISTINCT $fieldsStr" : $fieldsStr;
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

    protected function mountJoins(): string
    {
        if (empty($this->joins))
            return '';

        $result = [];
        foreach ($this->joins as $join) {
            $table = $join['table'];
            $condition = $join['condition'];

            if (!str_starts_with(trim($table), '(')) {
                if (!str_contains($table, ' ')) {
                    $table = "`$table`";
                } else {
                    $parts = explode(' ', $table, 2);
                    $table = "`{$parts[0]}` {$parts[1]}";
                }
            }

            $condition = preg_replace_callback('/\b([a-z_][a-z0-9_]*)\b/i', function ($match) {
                $token = strtolower($match[1]);
                return in_array($token, $this->sqlKeywords) ? $match[0] : "`{$match[1]}`";
            }, $condition);

            if (str_contains($condition, '.'))
                $condition = preg_replace('/`([^`]+)`\.`([^`]+)`/', '`$1`.`$2`', $condition);

            $result[] = " {$join['type']} JOIN $table ON $condition";
        }

        return implode('', $result);
    }

    protected function mountWhere(): string
    {
        $return = [];
        $parametros = 0;
        foreach ($this->where as $where) {
            if (count($where) == 1 || is_null($where[1])) {
                $expression = $where[0];
                if (is_string($expression)) {
                    $expression = preg_replace_callback('/\b([a-z_][a-z0-9_]*)\b/i', function ($match) {
                        $token = $match[1];
                        $lowToken = strtolower($token);
                        if (in_array($lowToken, $this->sqlKeywords) || is_numeric($token))
                            return $token;
                        return "`$token`";
                    }, $expression);

                    if (str_contains($expression, '.'))
                        $expression = preg_replace('/`([^`]+)`\.`([^`]+)`/', '`$1`.`$2`', $expression);
                }
                $return[] = $expression;
            } else {
                $expression = array_shift($where);
                if (!substr_count($expression, ' ') && !substr_count($expression, '?'))
                    $expression = "$expression = ?";

                $expression = preg_replace_callback('/\b([a-z_][a-z0-9_]*)\b/i', function ($match) {
                    $token = strtolower($match[1]);
                    return in_array($token, $this->sqlKeywords) ? $match[0] : "`{$match[1]}`";
                }, $expression);

                if (str_contains($expression, '.'))
                    $expression = preg_replace('/`([^`]+)`\.`([^`]+)`/', '`$1`.`$2`', $expression);

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
