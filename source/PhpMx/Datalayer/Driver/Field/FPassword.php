<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FPassword extends Field
{
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strval($value);
            if (!is_password($value))
                $value = password_hash($value, PASSWORD_BCRYPT);
        }

        return parent::set($value);
    }

    /** Verifica se um valor bate com o hash armazenado */
    function compare($value): bool
    {
        if (is_null($this->VALUE) || is_null($value))
            return false;

        return password_verify(strval($value), $this->VALUE);
    }
}
