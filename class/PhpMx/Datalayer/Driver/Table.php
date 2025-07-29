<?php

namespace PhpMx\Datalayer\Driver;

use PhpMx\Cif;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\Select;
use PhpMx\Datalayer\Driver\Record;
use Error;
use Exception;
use PhpMx\Log;

/** Classe base para drivers de tabela, responsável por consulta, cache e conversão de registros em objetos. */
abstract class Table
{
    protected $DATALAYER;
    protected $TABLE;

    protected $CLASS_RECORD;

    protected array $CACHE = [];
    protected bool $CACHE_STATUS = true;

    protected $ACTIVE;

    /** Retorna os esquemas dos registros */
    final function getAll_scheme(array $scheme = [], ...$args): array
    {
        return array_map(fn($record) => $record->_scheme($scheme), $this->getAll(...$args));
    }

    /** Retorna o esquema de um registro */
    final function getOne_scheme(array $scheme = [], ...$args): array
    {
        return $this->getOne(...$args)->_scheme($scheme);
    }

    /** Retorna o esquema de um registro baseando-se em uma idKey */
    final function getOneKey_scheme(array $scheme = [], ?string $idKey = null, ?string $errMessage = null, int $errCode = 404)
    {
        return $this->getOneKey($idKey, $errMessage, $errCode)->_scheme($scheme);
    }

    /** Retorna os esquemas completos dos registros */
    final function getAll_schemeAll(array $fieldsRemove = [], ...$args): array
    {
        return array_map(fn($record) => $record->_schemeAll($fieldsRemove), $this->getAll(...$args));
    }

    /** Retorna o esquema completo de um registro */
    final function getOne_schemeAll(array $fieldsRemove = [], ...$args): array
    {
        return $this->getOne(...$args)->_schemeAll($fieldsRemove);
    }

    /** Retorna o esquema completo de um registro baseando-se em uma idKey */
    final function getOneKey_schemeAll(array $fieldsRemove = [], ?string $idKey = null, ?string $errMessage = null, int $errCode = 404): array
    {
        return $this->getOneKey($idKey, $errMessage, $errCode)->_schemeAll($fieldsRemove);
    }

    /** Retorna o registro marcado como ativo */
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

    /** Retorna o numero de registro encontrados com uma busca */
    final function count(...$args): int
    {
        $query = $this->autoQuery(...$args)->fields(null, 'id');
        return count($query->run());
    }

    /** Verifica se existe ao menos um registro que correspondem a consulta */
    final function check(...$args): bool
    {
        $query = $this->autoQuery(...$args)->fields(null, 'id')->limit(1);
        return count($query->run());
    }

    /** Retorna um array de registros */
    final function getAll(...$args): array
    {
        $query = $this->autoQuery(...$args);

        $result = $this->_convert($query->run());

        return $result;
    }

    /** Retorna um registro */
    final function getOne(...$args)
    {
        if (!func_num_args() || $args[0] === 0)
            return $this->getNew();

        if (is_null($args[0] ?? null) || $args[0] === false)
            return $this->getNull();

        if ($args[0] === true)
            return $this->active();

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

    /** Retorna um registro baseando-se em uma idKey */
    final function getOneKey(?string $idKey = null, ?string $errMessage = null, int $errCode = 404)
    {
        $id = $this->idKeyToId($idKey);
        $record = $this->getOne($id);

        if (!is_null($errMessage) && !$record->_checkInDb())
            throw new Exception($errMessage, $errCode);

        return $record;
    }

    /** Retorna um registro novo */
    final function getNew(...$args)
    {
        return $this->arrayToRecord(['id' => 0]);
    }

    /** Retorna um registro nulo */
    final function getNull(...$args)
    {
        return $this->arrayToRecord(['id' => null]);
    }

    /** Converte um ID em IdKey */
    final function idToIdkey(?int $id): string
    {
        return Cif::on([$this->TABLE, $id], $this->TABLE);
    }

    /** Converte um IdKey em ID */
    final function idKeyToId(?string $idKey): ?int
    {
        if (Cif::check($idKey)) {
            $array = Cif::off($idKey);
            if (is_array($array) && array_shift($array) == $this->TABLE)
                return array_shift($array);
        }
        return null;
    }

    /** Converte um array de consula em um array de objetos de registro */
    final function _convert(array $arrayRecord): array
    {
        foreach ($arrayRecord as $array)
            $result[] = $this->arrayToRecord($array);

        return $result ?? [];
    }

    /** Monta o objeto de queru baseando-se nos parametros fornecidos */
    protected function autoQuery(...$args): Select
    {
        switch ($this->typeQuery(...$args)) {
            case 1; //Query Limpa
                $query = Query::select();
                break;
            case 2; //Busca por ID
                $query = Query::select();
                $query->where('id', $args[0]);
                break;
            case 3; //Busca por where informado
                $query = Query::select();
                $query->where($args[0], $args[1] ?? null);
                break;
            case 4; //Busca por where dinamico informado via array
                $query = Query::select();
                foreach ($args[0] as $key => $value)
                    $query->where($key, $value);
                break;
            case 5; //Busca utilizando select personalizado
                $query = $args[0];
                $query->fields(null)->table(null);
                break;
            default; //Impossivel definir
                throw new Error('Impossible to create query with provided parameters');
                break;
        }
        $query->dbName($this->DATALAYER)->table($this->TABLE);

        return $query;
    }

    /** Retorna o tipo da query baseando-se nos parametros fornecidos */
    protected function typeQuery(...$args)
    {
        $param = $args[0] ?? null;

        if (is_null($param))
            return 1; //Query Limpa

        if (is_numeric($param) && intval($param) == $param && count($args) == 1)
            return 2; //Busca por ID

        if (is_string($param))
            return 3; //Busca por where informado

        if (is_array($param))
            return 4; //Busca por where dinamico informado via array

        if (is_class($param, Select::class))
            return 5; //Busca utilizando select personalizado

        return 0; //Impossivel definir
    }

    /** Conerte um array em um objeto de registro */
    protected function arrayToRecord(array $array): Record
    {
        $id = $array['id'] ?? null;
        $classRecord = $this->CLASS_RECORD;

        if (is_null($id))
            return new $classRecord(['id' => null]);

        if (!$id)
            return new $classRecord(['id' => 0]);

        if ($this->__cacheCheck())
            return $this->recordCache($array);

        return new $classRecord($array);
    }

    /** Verifica se um registro está armazenado em cache */
    protected function inCache($id): bool
    {
        return $this->__cacheCheck() && isset($this->CACHE[$id]);
    }

    /** Retorna um objeto de registro armazenado em cache */
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

    /** Armazena um objeto de registro em cache */
    function __cacheSet(int $id, Record &$record): void
    {
        if ($this->__cacheCheck())
            $this->CACHE[$id] = $record;
    }

    /** Remove um objeto armazenado em cache */
    function __cacheRemove(int $id): void
    {
        if ($this->inCache($id))
            unset($this->CACHE[$id]);
    }

    /** Ativa ou desativa o uso do cache */
    function __cacheStauts(bool $status): void
    {
        $this->CACHE_STATUS = $status;
    }

    /** Verifica o status do cache */
    function __cacheCheck(): bool
    {
        return !IS_TERMINAL && $this->CACHE_STATUS;
    }
}
