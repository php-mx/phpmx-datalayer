<?php

namespace PhpMx\Datalayer\Driver;

use PhpMx\Cif;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\Select;
use PhpMx\Datalayer\Driver\Record;
use Error;
use Exception;
use PhpMx\Log;

/** @ignore */
abstract class Table
{
    protected $DATALAYER;
    protected $TABLE;

    protected $CLASS_RECORD;

    protected array $CACHE = [];
    protected bool $CACHE_STATUS = true;

    protected $ACTIVE;

    protected $SHOW_DELETED = false;

    /**
     * Define se na próxima consulta os dados maracados como removidos deve ser exibidos.
     * @param bool|null $showDeleted TRUE: Apenas removidos, FLASE: Apenas não removidos, NULL: Mostrar ambos
     * @return static
     */
    final function showDeleted(?bool $showDeleted): self
    {
        $this->SHOW_DELETED = $showDeleted;
        return $this;
    }

    /**
     * Retorna os esquemas dos registros encontrados pela consulta.
     * @param array $scheme Campos do esquema a retornar.
     * @param mixed ...$args Parâmetros de consulta.
     * @return array
     */
    final function getAll_scheme(array $scheme = [], ...$args): array
    {
        return array_map(fn($record) => $record->_scheme($scheme), $this->getAll(...$args));
    }

    /**
     * Retorna o esquema de um único registro encontrado pela consulta.
     * @param array $scheme Campos do esquema a retornar.
     * @param mixed ...$args Parâmetros de consulta.
     * @return array
     */
    final function getOne_scheme(array $scheme = [], ...$args): array
    {
        return $this->getOne(...$args)->_scheme($scheme);
    }

    /**
     * Retorna o esquema de um registro buscado por idKey.
     * @param array $scheme Campos do esquema a retornar.
     * @param string|null $idKey IdKey do registro.
     * @param string|null $errMessage Mensagem de erro caso o registro não seja encontrado.
     * @param int $errCode Código HTTP do erro (padrão 404).
     */
    final function getOneKey_scheme(array $scheme = [], ?string $idKey = null, ?string $errMessage = null, int $errCode = 404)
    {
        return $this->getOneKey($idKey, $errMessage, $errCode)->_scheme($scheme);
    }

    /**
     * Retorna os esquemas completos dos registros encontrados pela consulta.
     * @param array $fieldsRemove Campos a remover do esquema completo.
     * @param mixed ...$args Parâmetros de consulta.
     * @return array
     */
    final function getAll_schemeAll(array $fieldsRemove = [], ...$args): array
    {
        return array_map(fn($record) => $record->_schemeAll($fieldsRemove), $this->getAll(...$args));
    }

    /**
     * Retorna o esquema completo de um único registro encontrado pela consulta.
     * @param array $fieldsRemove Campos a remover do esquema completo.
     * @param mixed ...$args Parâmetros de consulta.
     * @return array
     */
    final function getOne_schemeAll(array $fieldsRemove = [], ...$args): array
    {
        return $this->getOne(...$args)->_schemeAll($fieldsRemove);
    }

    /**
     * Retorna o esquema completo de um registro buscado por idKey.
     * @param array $fieldsRemove Campos a remover do esquema completo.
     * @param string|null $idKey IdKey do registro.
     * @param string|null $errMessage Mensagem de erro caso o registro não seja encontrado.
     * @param int $errCode Código HTTP do erro (padrão 404).
     * @return array
     */
    final function getOneKey_schemeAll(array $fieldsRemove = [], ?string $idKey = null, ?string $errMessage = null, int $errCode = 404): array
    {
        return $this->getOneKey($idKey, $errMessage, $errCode)->_schemeAll($fieldsRemove);
    }

    /**
     * Retorna ou define o registro marcado como ativo na tabela.
     * @param mixed ...$args Sem argumentos retorna o ativo atual. Com argumentos define o novo registro ativo.
     */
    final function active($make = null)
    {
        if (func_num_args()) {
            $make = is_class($make, $this->CLASS_RECORD) ? $make : $this->getOne(...func_get_args());
            $this->ACTIVE = Log::add('driver.make.active', prepare('[#].[#]([#])', [
                strToPascalCase("db $this->DATALAYER"),
                strToCamelCase($this->TABLE),
                str_get_var($make->id())
            ]), fn() => $make);
        }

        return $this->ACTIVE ?? $this->getNull();
    }

    /**
     * Retorna o número de registros encontrados pela consulta.
     * @param mixed ...$args Parâmetros de consulta.
     * @return int
     */
    final function count(...$args): int
    {
        $query = $this->autoQuery(...$args)->fields(null, 'id');
        return count($query->run());
    }

    /**
     * Verifica se existe ao menos um registro que corresponde à consulta.
     * @param mixed ...$args Parâmetros de consulta.
     * @return bool
     */
    final function check(...$args): bool
    {
        $query = $this->autoQuery(...$args)->fields(null, 'id')->limit(1);
        return count($query->run());
    }

    /**
     * Retorna um array de objetos de registro encontrados pela consulta.
     * @param mixed ...$args Parâmetros de consulta.
     * @return array
     */
    final function getAll(...$args): array
    {
        $query = $this->autoQuery(...$args);

        $result = $this->_convert($query->run());

        return $result;
    }

    /**
     * Retorna um único objeto de registro encontrado pela consulta.
     * Aceita: sem args (novo), null/false (nulo), true (ativo), idKey, id numérico, where string ou array.
     * @param mixed ...$args Parâmetros de consulta.
     */
    final function getOne(...$args)
    {
        if (!func_num_args() || $args[0] === 0)
            return $this->getNew();

        if (is_null($args[0] ?? null) || $args[0] === false)
            return $this->getNull();

        if ($args[0] === true)
            return $this->active();

        if (is_idKey($args[0]))
            return $this->getOneKey($args[0]);

        if ($this->typeQuery(...$args) == 2 && $this->inCache($args[0]))
            return Log::add('driver.select.ignored', prepare('[#].[#]([#]) has already been loaded', [
                strToPascalCase("db $this->DATALAYER"),
                strToCamelCase($this->TABLE),
                $args[0]
            ]), fn() => $this->recordCache(['id' => $args[0]]));

        $result = Log::add('driver.select', [
            'unknown',
            'by query',
            'by id',
            'by where provided',
            'by dynamic where informed via array',
            'by custom select'
        ][$this->typeQuery(...$args)], fn() => $this->autoQuery(...$args)->limit(1)->run());

        return empty($result) ? $this->getNull() : $this->arrayToRecord(array_shift($result));
    }

    /**
     * Retorna um registro buscado por idKey, lançando Exception se não encontrado e errMessage for informado.
     * @param string|null $idKey IdKey do registro.
     * @param string|null $errMessage Mensagem de erro caso o registro não seja encontrado.
     * @param int $errCode Código HTTP do erro (padrão 404).
     */
    final function getOneKey(?string $idKey = null, ?string $errMessage = null, int $errCode = 404)
    {
        $id = $this->idKeyToId($idKey);
        $record = $this->getOne($id);

        if (!is_null($errMessage) && !$record->_checkInDb())
            throw new Exception($errMessage, $errCode);

        return $record;
    }

    /**
     * Retorna um objeto de registro novo (id = 0).
     */
    final function getNew()
    {
        return $this->arrayToRecord(['id' => 0]);
    }

    /**
     * Retorna um objeto de registro nulo (id = null).
     */
    final function getNull()
    {
        return $this->arrayToRecord(['id' => null]);
    }

    /**
     * Converte um ID numérico em idKey cifrado.
     * @param int|null $id ID do registro.
     * @return string
     */
    final function idToIdkey(?int $id): string
    {
        return Cif::on([$this->TABLE, $id], $this->TABLE);
    }

    /**
     * Converte um idKey cifrado em ID numérico.
     * @param string|null $idKey IdKey do registro.
     * @return int|null
     */
    final function idKeyToId(?string $idKey): ?int
    {
        if (Cif::check($idKey)) {
            $array = Cif::off($idKey);
            if (is_array($array) && array_shift($array) == $this->TABLE)
                return array_shift($array);
        }
        return null;
    }

    /**
     * Converte um array de resultados de consulta em um array de objetos de registro.
     * @param array $arrayRecord Array de arrays de dados.
     * @return array
     */
    final function _convert(array $arrayRecord): array
    {
        foreach ($arrayRecord as $array)
            $result[] = $this->arrayToRecord($array);

        return $result ?? [];
    }

    /**
     * Armazena um objeto de registro no cache da tabela.
     * @param int $id ID do registro.
     * @param Record $record Objeto de registro a armazenar.
     */
    function __cacheSet(int $id, Record &$record): void
    {
        if ($this->__cacheCheck() && is_null($record->_deleted()))
            $this->CACHE[$id] = $record;
    }

    /**
     * Remove um objeto de registro do cache da tabela.
     * @param int $id ID do registro a remover.
     */
    function __cacheRemove(int $id): void
    {
        if ($this->inCache($id))
            unset($this->CACHE[$id]);
    }

    /**
     * Ativa ou desativa o uso do cache na tabela.
     * @param bool $status Se verdadeiro ativa o cache.
     */
    function __cacheStauts(bool $status): void
    {
        $this->CACHE_STATUS = $status;
    }

    /**
     * Verifica se o cache está ativo.
     * @return bool
     */
    function __cacheCheck(): bool
    {
        return !IS_TERMINAL && $this->CACHE_STATUS;
    }

    /**
     * Constrói uma query Select a partir dos argumentos fornecidos usando typeQuery() para determinar o modo.
     * @param mixed ...$args Parâmetros de consulta.
     * @return Select
     */
    protected function autoQuery(...$args): Select
    {
        switch ($this->typeQuery(...$args)) {
            case 1;
                $query = Query::select();
                break;
            case 2;
                $query = Query::select();
                $query->where('id', $args[0]);
                break;
            case 3;
                $query = Query::select();
                $query->where($args[0], $args[1] ?? null);
                break;
            case 4;
                $query = Query::select();
                foreach ($args[0] as $key => $value)
                    $query->where($key, $value);
                break;
            case 5;
                $query = $args[0];
                $query->fields(null)->table(null);
                break;
            default;
                throw new Error('Impossible to create query with provided parameters');
                break;
        }
        $query->dbName($this->DATALAYER)->table($this->TABLE);
        if (!is_null($this->SHOW_DELETED)) $query->whereNull('_deleted', !$this->SHOW_DELETED);
        $this->SHOW_DELETED = false;
        return $query;
    }

    /**
     * Determina o tipo de consulta com base nos argumentos: 1=all, 2=by id, 3=by where string, 4=by array, 5=by Select.
     * @param mixed ...$args Parâmetros de consulta.
     * @return int Código do tipo de consulta.
     */
    protected function typeQuery(...$args)
    {
        $param = $args[0] ?? null;
        if (is_null($param)) return 1;
        if (is_numeric($param) && intval($param) == $param && count($args) == 1) return 2;
        if (is_string($param)) return 3;
        if (is_array($param)) return 4;
        if (is_class($param, Select::class)) return 5;
        return 0;
    }

    /**
     * Converte um array de dados em um objeto Record, usando cache quando disponível.
     * @param array $array Dados do registro (deve conter 'id').
     * @return Record
     */
    protected function arrayToRecord(array $array): Record
    {
        $id = $array['id'] ?? null;
        $classRecord = $this->CLASS_RECORD;
        if (is_null($id)) return new $classRecord(['id' => null]);
        if (!$id) return new $classRecord(['id' => 0]);
        if ($this->__cacheCheck() && is_null($array['_deleted'])) return $this->recordCache($array);
        return new $classRecord($array);
    }

    /**
     * Verifica se um registro com o ID informado está presente no cache da tabela.
     * @param int $id ID do registro.
     * @return bool
     */
    protected function inCache($id): bool
    {
        return $this->__cacheCheck() && isset($this->CACHE[$id]);
    }

    /**
     * Retorna o Record do cache se existir, ou cria e armazena um novo a partir do array.
     * @param array $array Dados do registro.
     * @return Record
     */
    protected function &recordCache($array): Record
    {
        $id = $array['id'];
        $classRecord = $this->CLASS_RECORD;

        if ($this->__cacheCheck()) {
            $this->CACHE[$id] = $this->CACHE[$id] ?? new $classRecord($array);
            return $this->CACHE[$id];
        } else {
            return new $classRecord($array);
        }
    }
}
