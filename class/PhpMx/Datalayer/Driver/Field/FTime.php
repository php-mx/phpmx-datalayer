<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um instante em forma de inteiro */
class FTime extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_bool($value))
            $value = $value ? time() : 0;

        if (is_string($value))
            $value = ctype_digit($value) ? intval($value) : strtotime(str_replace('/', '-', $value));

        $value = is_numeric($value) ? intval($value) : null;

        return parent::set($value);
    }
}
