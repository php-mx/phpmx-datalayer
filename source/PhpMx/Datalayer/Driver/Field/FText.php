<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de texto longo (TEXT), com conversão automática do valor para string. */
class FText extends Field
{
    function set($value): static
    {
        if (!is_null($value))
            $value = strval($value);

        return parent::set($value);
    }
}
