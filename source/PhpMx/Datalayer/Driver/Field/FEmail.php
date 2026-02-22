<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;

class FEmail extends FVarchar
{
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strtolower(strval($value));
            $value = remove_accents($value);
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        return parent::set($value);
    }

    protected function validade(mixed $value): void
    {
        parent::validade($value);

        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL))
            throw new Exception("The value is not a valid email [$this->NAME]");
    }
}
