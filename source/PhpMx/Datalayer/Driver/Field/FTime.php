<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FTime extends Field
{
    function set($value): static
    {
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('H:i:s', $value);
        return parent::set($value);
    }
}
