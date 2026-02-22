<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FMd5 extends Field
{
    function set($value): static
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return parent::set($value);
    }

    function compare($value): bool
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return $value == $this->VALUE;
    }
}
