<?php

namespace PhpMx\Datalayer\Connection;

use Exception;
use PDO;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\BaseQuery;
use PhpMx\Json;
use PhpMx\Log;
use Throwable;

abstract class BaseConnection
{
    protected string $dbName;

    protected ?array $config = null;

    protected $instancePDO;

    protected string $pdoDriver;

    /** Inicializa a conexão */
    abstract protected function load();

    /** Retorna a instancia PDO da conexão */
    abstract protected function &pdo(): PDO;

    /** Query para criação de tabelas */
    abstract protected function schemeQueryCreateTable(string $name, ?string $comment, array $fields): array;

    /** Query para alteração de tabelas */
    abstract protected function schemeQueryAlterTable(string $name, ?string $comment, array $fields): array;

    /** Query para remoção de tabelas */
    abstract protected function schemeQueryDropTable(string $name): array;

    /** Query para remoção de tabelas */
    abstract protected function schemeQueryUpdateTableIndex(string $name, array $index): array;

    /** Carrega as configurações do banco de dados para o cache */
    abstract protected function loadConfig(): void;

    final function __construct(string $dbName, protected array $data = [])
    {
        if (!extension_loaded($this->pdoDriver))
            throw new Exception("Extension [{$this->pdoDriver}] is required.");

        $this->dbName = $dbName;
        $this->load();
        foreach ($this->data as $var => $value)
            if (is_null($value))
                throw new Exception("parameter [$var] required in [{$this->data['type']}] datalayer");
    }

    /** Retorna uma configuração armazenada no banco */
    function getConfig(?string $name = null)
    {
        $this->loadConfig();

        return is_null($name) ?  $this->config : $this->config[$name] ?? null;
    }

    /** Armazena uma configuração no banco */
    function setConfig($name, $value)
    {
        $this->loadConfig();

        if (in_array($name, array_keys($this->config))) {
            $query = Query::update('__config')->where('name', $name)->values(['value' => serialize($value)]);
        } else {
            $query = Query::insert('__config')->values(['name' => $name, 'value' => serialize($value)]);
        }
        $this->executeQuery($query);
        $this->config[$name] = $value;
    }

    /** Executa uma query */
    function executeQuery(string|BaseQuery $query, array $data = []): mixed
    {
        if (is_class($query, BaseQuery::class))
            list($query, $data) = $query->query();

        return Log::add('datalayer.query', $query, function () use ($query, $data) {

            $pdoQuery = $this->pdo()->prepare($query);

            if (!$pdoQuery)
                throw new Exception("[$query]");

            if (!$pdoQuery->execute($data)) {
                $error = $pdoQuery->errorInfo();
                $error = $error[2] ?? '-undefined-';
                throw new Exception("[$query] [$error]");
            }

            $type = strtolower(strtok(trim($query), ' '));

            return match ($type) {
                'update', 'delete' => true,
                'insert' => $this->pdo()->lastInsertId(),
                'select', 'show', 'pragma' => $pdoQuery->fetchAll(PDO::FETCH_ASSOC),
                default => $pdoQuery
            };
        });
    }

    /** Executa uma lista de  querys */
    function executeQueryList(array $queryList = [], bool $transaction = true): array
    {
        try {
            if ($transaction) $this->pdo()->beginTransaction();
            foreach ($queryList as &$query) {
                $queryParams = is_array($query) ? $query : [$query];
                $query = $this->executeQuery(...$queryParams);
            }
            if ($transaction) $this->pdo()->commit();
        } catch (Throwable $e) {
            if ($transaction) $this->pdo()->rollBack();
            throw $e;
        }
        return $queryList;
    }

    /** Executa uma lista de querys de esquema */
    function executeSchemeQuery(array $schemeQueryList): void
    {
        $queryList = [];

        foreach ($schemeQueryList as $schemeQuery) {
            list($action, $data) = $schemeQuery;
            array_push($queryList, ...match ($action) {
                'create' => $this->schemeQueryCreateTable(...$data),
                'alter' => $this->schemeQueryAlterTable(...$data),
                'drop' => $this->schemeQueryDropTable(...$data),
                'index' => $this->schemeQueryUpdateTableIndex(...$data),
                default => []
            });
        }

        $this->executeQueryList($queryList, false);
    }
}
