<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de data e hora (DATETIME), com conversão automática de timestamp inteiro e CURRENT_TIMESTAMP para string no formato Y-m-d H:i:s. */
class FDatetime extends Field
{
    /**
     * Define o valor de data e hora. Aceita timestamp inteiro, true/CURRENT_TIMESTAMP (usa hora atual), false (null) ou string formatada.
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        if ($value === true || $value === 'CURRENT_TIMESTAMP') $value = time();
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('Y-m-d H:i:s', $value);
        return parent::set($value);
    }
}
