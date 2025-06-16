<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena dados Booleanos 1 ou 0 */
class FBoolean extends Field
{
    /** Define um novo valor para o campo */
    function set($value)
    {
        $value = is_null($value) ? null : boolval($value);
        parent::set($value);
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue()
    {
        $value = parent::__internalValue();

        if (is_bool($value))
            $value = intval($value);

        return $value;
    }
}
