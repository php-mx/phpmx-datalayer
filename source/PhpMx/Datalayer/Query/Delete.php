<?php

namespace PhpMx\Datalayer\Query;

/**
 * Monta e executa instruções SQL do tipo DELETE com suporte a cláusulas WHERE e ORDER BY.
 */
class Delete extends BaseQuery
{
    protected array $order = [];
    protected array $where = [];

    /**
     * Retorna o array de dados necessários para execução da query DELETE.
     * @return array
     */
    function query(): array
    {
        $this->check(['table', 'where']);

        $query = 'DELETE FROM [#table] [#where][#order];';

        $query = prepare($query, [
            'table' => $this->mountTable(),
            'where' => $this->mountWhere(),
            'order' => $this->mountOrder(),
        ]);

        $values = [];
        $parametros = 0;

        foreach ($this->where as $where) {
            if (count($where) > 1 && !is_null($where[1])) {
                array_shift($where);
                foreach ($where as $v) {
                    $values['where_' . ($parametros++)] = $v;
                }
            }
        }

        return [$query, $values];
    }

    /**
     * Executa a query DELETE no banco de dados.
     * @param string|null $dbName Nome do banco de dados (opcional, usa 'main' por padrão).
     * @return bool
     */
    function run(?string $dbName = null): bool
    {
        return parent::run($dbName);
    }

    /**
     * Define a ordenação dos registros a deletar.
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
        if (is_string($ids)) $ids = explode(',', $ids);
        $ids = array_filter($ids, fn($id) => is_int($id));
        if (!count($ids)) return $this->where('false');

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

    protected function mountOrder(): string
    {
        if (empty($this->order))
            return '';

        return " ORDER BY " . implode(', ', $this->order);
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
                if (!str_contains($expression, ' ') && !str_contains($expression, '?'))
                    $expression = "$expression = ?";

                $expression = preg_replace_callback('/\b([a-z_][a-z0-9_]*)\b/i', function ($match) {
                    $token = strtolower($match[1]);
                    return in_array($token, $this->sqlKeywords) ? $match[0] : "`{$match[1]}`";
                }, $expression);

                if (str_contains($expression, '.'))
                    $expression = preg_replace('/`([^`]+)`\.`([^`]+)`/', '`$1`.`$2`', $expression);

                $expression = str_replace(["'?'", '"?"'], '?', $expression);
                foreach ($where as $v)
                    $expression = str_replace_first('?', ":where_" . ($parametros++), $expression);

                $return[] = $expression;
            }
        }
        $return = array_filter($return);
        return empty($return) ? '' : 'WHERE (' . implode(') AND (', $return) . ')';
    }
}
