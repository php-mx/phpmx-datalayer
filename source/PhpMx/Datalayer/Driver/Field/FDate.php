<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de data (DATE), com conversão automática de timestamp inteiro para string no formato Y-m-d. */
class FDate extends Field
{
    function set($value): static
    {
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('Y-m-d', $value);
        return parent::set($value);
    }
}
