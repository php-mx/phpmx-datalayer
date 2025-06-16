<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** armazena configurações e seus valores em forma de JSON */
class FConfig extends Field
{
    /** Define um novo valor para o campo */
    function set($value)
    {
        if (func_num_args() == 2)
            return $this->setIn(...func_get_args());

        if (is_json($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = [];

        $value = array_filter($value, fn($v) => is_stringable($v));

        return parent::set($value);
    }

    /** Retorna o valor do campo ou o valor de uma configação do campo */
    function get()
    {
        if (func_num_args() == 1)
            return $this->getIn(...func_get_args());

        return parent::get();
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue()
    {
        $value = parent::__internalValue();

        return json_encode($value);
    }

    /** Define uma configuração no campo */
    function setIn($var, $value)
    {
        $this->VALUE[$var] = $value;

        $this->VALUE = array_filter($this->VALUE, fn($v) => is_stringable($v));
    }

    /** Retorna uma configuração do campo */
    function getIn($var)
    {
        return $this->VALUE[$var] ?? null;
    }

    /** Remove uma configuração do campo */
    function checkIn($var)
    {
        return is_null($this->getIn($var));
    }

    /** Verifica se uma configuração existe no campo */
    function removeIn($var)
    {
        return $this->setIn($var, null);
    }
}
