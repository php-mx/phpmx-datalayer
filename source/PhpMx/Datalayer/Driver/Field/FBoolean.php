<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FBoolean extends Field
{
    function set($value): static
    {
        $value = is_null($value) ? null : boolval($value);

        return parent::set($value);
    }

    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        if (is_bool($value))
            $value = intval($value);

        if ($validate) $this->validade($value);

        return $value;
    }
}
