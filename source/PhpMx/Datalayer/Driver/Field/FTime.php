<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de tempo (TIME), com conversão automática de timestamp inteiro para string no formato H:i:s. */
class FTime extends Field
{
    /**
     * Define o valor de hora. Aceita timestamp inteiro (convertido para H:i:s) ou false (null).
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('H:i:s', $value);
        return parent::set($value);
    }
}
