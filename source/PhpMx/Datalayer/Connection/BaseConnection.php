<?php

namespace PhpMx\Datalayer\Connection;

use Exception;
use PDO;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\BaseQuery;
use PhpMx\Log;
use Throwable;

/** Base para drivers de conexão. */
abstract class BaseConnection
{
    protected string $dbName;

    protected $instancePDO;

    protected bool $configInitialized = false;

    protected string $pdoDriver;

    /**
     * Carrega as configurações da conexão a partir das variáveis de ambiente e inicializa o DSN.
     */
    abstract protected function load();

    /**
     * Retorna a instância PDO da conexão, criando-a na primeira chamada.
     * @return PDO
     */
    abstract protected function &pdo(): PDO;

    /**
     * Retorna as queries SQL necessárias para criar uma tabela com os campos informados.
     * @param string $name Nome da tabela.
     * @param string|null $comment Comentário da tabela.
     * @param array $fields Campos a incluir na criação.
     * @return array
     */
    abstract protected function schemeQueryCreateTable(string $name, ?string $comment, array $fields): array;

    /**
     * Retorna as queries SQL necessárias para alterar uma tabela existente.
     * @param string $name Nome da tabela.
     * @param string|null $comment Comentário da tabela.
     * @param array $fields Campos a adicionar, alterar ou remover.
     * @return array
     */
    abstract protected function schemeQueryAlterTable(string $name, ?string $comment, array $fields): array;

    /**
     * Retorna as queries SQL necessárias para remover uma tabela.
     * @param string $name Nome da tabela.
     * @return array
     */
    abstract protected function schemeQueryDropTable(string $name): array;

    /**
     * Retorna as queries SQL necessárias para criar ou remover índices de uma tabela.
     * @param string $name Nome da tabela.
     * @param array $index Mapa de índices [indexName => [campo, unique] | false].
     * @return array
     */
    abstract protected function schemeQueryUpdateTableIndex(string $name, array $index): array;

    /**
     * Garante que a tabela __config exista no banco de dados, criando-a se necessário.
     */
    abstract protected function initConfig(): void;

    /** @ignore */
    final function __construct(string $dbName, protected array $data = [])
    {
        phpex($this->pdoDriver);

        $this->dbName = $dbName;
        $this->load();
        foreach ($this->data as $var => $value)
            if (is_null($value))
                throw new Exception("parameter [$var] required in [{$this->data['type']}] datalayer");
    }

    /**
     * Retorna todas as configurações de um grupo armazenadas no banco.
     * @param string $group Nome do grupo de configurações.
     * @return array
     */
    function getConfigGroup(string $group): array
    {
        $this->initConfig();
        $results = Query::select('__config')->where('group', $group)->order('id', true)->run($this->dbName);

        $data = [];
        foreach ($results as $item) {
            $val = is_serialized($item['value']) ? unserialize($item['value']) : $item['value'];
            $key = is_numeric($item['name']) ? (int)$item['name'] : $item['name'];
            $data[$key] = $val;
        }
        return $data;
    }

    /**
     * Armazena ou substitui todas as configurações de um grupo no banco.
     * @param string $group Nome do grupo de configurações.
     * @param array $values Array associativo [nome => valor] a armazenar.
     */
    function setConfigGroup(string $group, array $values)
    {
        $this->initConfig();

        Query::delete('__config')->where('group', $group)->run($this->dbName);

        $rowsToInsert = [];
        foreach ($values as $name => $value)
            $rowsToInsert[] = [
                'group' => $group,
                'name'  => $name,
                'value' => is_serialized($value) ? $value : serialize($value)
            ];

        if (!empty($rowsToInsert))
            Query::insert('__config')->values(...$rowsToInsert)->run($this->dbName);
    }

    /**
     * Executa uma query SQL ou objeto BaseQuery e retorna o resultado.
     * @param string|BaseQuery $query Query SQL ou objeto de query.
     * @param array $data Parâmetros a vincular na query (opcional).
     * @return mixed
     */
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

    /**
     * Executa uma lista de queries, opcionalmente dentro de uma transação.
     * @param array $queryList Lista de queries ou arrays [query, params] a executar.
     * @param bool $transaction Se verdadeiro envolve a execução em uma transação.
     * @return array
     */
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

    /**
     * Executa uma lista de queries de esquema (create, alter, drop, index).
     * @param array $schemeQueryList Lista de operações de esquema a aplicar.
     */
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
