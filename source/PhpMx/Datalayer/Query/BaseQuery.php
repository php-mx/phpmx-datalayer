<?php

namespace PhpMx\Datalayer\Query;

use Error;
use PhpMx\Datalayer;

/** Classe base para todos os query builders. Fornece tabela, dbName, execução e montagem de SQL. */
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

    /** @ignore */
    function __construct(null|string|array $table = null)
    {
        if ($table)
            $this->table($table);
    }

    /**
     * Retorna o array de dados necessários para execução da query.
     * @return array
     */
    abstract function query(): array;

    /**
     * Verifica se os campos obrigatórios da query foram definidos.
     * @param array $dataCheck Lista de propriedades a verificar.
     * @throws \Error Se algum campo obrigatório estiver vazio.
     */
    protected function check(array $dataCheck = []): void
    {
        foreach ($dataCheck as $check)
            if (empty($this->$check))
                throw new Error("Defina um valor de [$check] para a query");
    }

    /**
     * Executa a query no banco de dados e retorna o resultado.
     * @param string|null $dbName Nome do banco de dados (opcional, usa 'main' por padrão).
     * @return mixed
     */
    function run(?string $dbName = null): mixed
    {
        return Datalayer::get($this->dbName ?? $dbName ?? 'main')->executeQuery($this);
    }

    /**
     * Define o banco de dados que deve receber a query.
     * @param string|null $dbName Nome do banco de dados.
     * @return static
     */
    function dbName(?string $dbName): static
    {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * Define a tabela alvo da query.
     * @param string|array|null $table Nome da tabela (string), array name=>alias, ou null.
     * @return static
     */
    function table(null|string|array $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Monta a cláusula FROM da query aplicando backtick-quoting.
     * Suporta tabela simples, tabela com alias (array), notação schema.tabela e tabela com alias inline.
     * @return string Fragmento SQL da tabela.
     */
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
