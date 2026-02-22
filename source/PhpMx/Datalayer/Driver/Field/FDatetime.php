<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FDatetime extends Field
{
    function set($value): static
    {
        if ($value === true || $value === 'CURRENT_TIMESTAMP') $value = time();
        if ($value === false) $value = null;
        if (is_int($value)) $value = date('Y-m-d H:i:s', $value);
        return parent::set($value);
    }
}
