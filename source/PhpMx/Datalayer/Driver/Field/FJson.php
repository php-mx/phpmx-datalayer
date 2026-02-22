<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FJson extends Field
{
    function set($value): static
    {
        if (is_string($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = null;

        return parent::set($value);
    }

    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        if (!is_null($value))
            $value = json_encode($value);

        if ($validate) $this->validade($value);

        return $value;
    }
}
