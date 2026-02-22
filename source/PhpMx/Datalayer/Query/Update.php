<?php

namespace PhpMx\Datalayer\Query;

use Error;

/**
 * Monta e executa instruções SQL do tipo UPDATE com suporte a cláusulas WHERE, whereIn e whereNull.
 */
class Update extends BaseQuery
{
    protected array $values = [];
    protected $where = [];

    /**
     * Retorna o array de dados necessários para execução da query UPDATE.
     * @return array
     */
    function query(): array
    {
        $this->check(['table', 'where', 'values']);

        $query = 'UPDATE [#table] SET [#values] [#where];';

        $query = prepare($query, [
            'table' => $this->mountTable(),
            'values' => $this->mountValues(),
            'where' => $this->mountWhere(),
        ]);

        $values = [];
        $count = 0;

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

    /**
     * Executa a query UPDATE no banco de dados.
     * @param string|null $dbName Nome do banco de dados (opcional, usa 'main' por padrão).
     * @return bool
     */
    function run(?string $dbName = null): bool
    {
        return parent::run($dbName);
    }

    /**
     * Define os campos e valores a serem alterados.
     * @param array $array Array associativo [campo => valor] com os dados a atualizar.
     * @return static
     */
    function values(array $array): static
    {
        foreach ($array as $field => $value) {
            $this->values[$field] = $value;
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

    protected function mountValues(): string
    {
        $change = [];
        foreach ($this->values as $name => $value) {
            if (is_numeric($name)) {
                $change[] = "`$value` = NULL";
            } else if (is_null($value)) {
                $change[] = "`$name` = NULL";
            } else {
                $change[] = "`$name` = :value_$name";
            }
        }
        return implode(', ', $change);
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

    protected function mountTable(): string
    {
        if (is_array($this->table))
            throw new Error("Query UPDATE can only contain one value for [table]");

        return parent::mountTable();
    }
}
