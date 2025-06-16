<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena uma variavel em forma de texto livre */
class FText extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_stringable($value))
            $value = trim($value);

        return parent::set($value);
    }
}
