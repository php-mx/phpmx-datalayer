<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FDate extends Field
{
    function set($value): static
    {
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('Y-m-d', $value);
        return parent::set($value);
    }
}
