<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FText extends Field
{
    function set($value): static
    {
        if (!is_null($value))
            $value = strval($value);

        return parent::set($value);
    }
}
