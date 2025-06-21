<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\Select;

/** Armazena IDs de referencia para uma tabela */
class FIds extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_stringable($value))
            $value = explode(',', $value);

        if (!is_array($value))
            $value = [];

        $value = array_map(fn($v) => intval($v), $value);
        $value = array_filter($value, fn($v) => boolval($v));
        $value = array_unique($value);
        sort($value);

        return parent::set($value);
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        $value = implode(',', $value);

        if ($validate) $this->validade($value);

        return $value;
    }

    /** Define uma configuração no campo */
    function setIn(string|array|int $item): static
    {
        if (is_string($item))
            $item = explode(',', $item);

        if (!is_array($item))
            $item = [$item];

        foreach ($item as $id)
            $this->VALUE[] = $id;

        $this->VALUE = array_map(fn($v) => intval($v), $this->VALUE);
        $this->VALUE = array_filter($this->VALUE, fn($v) => boolval($v));
        $this->VALUE = array_unique($this->VALUE);

        return $this;
    }

    /** Remove uma configuração do campo */
    function checkIn($var)
    {
        return in_array($var, $this->VALUE);
    }

    /** Verifica se uma configuração existe no campo */
    function removeIn(string|array|int $item): static
    {
        if (is_string($item))
            $item = explode(',', $item);

        if (!is_array($item))
            $item = [$item];

        foreach ($item as $id)
            foreach ($this->VALUE as $pos => $inValueID)
                if ($inValueID == $id)
                    unset($this->VALUE[$pos]);

        return $this;
    }

    /** Retorna um SELECT buscando todos os registros representados no objeto */
    function query(): Select
    {
        $values = $this->__internalValue();
        $datalayer = $this->SETTINGS['datalayer'];
        $table = $this->SETTINGS['table'];
        $where = empty($values) ? 'false' : "id in ($values)";

        return Query::select($table)->dbName($datalayer)->where($where);
    }
}
