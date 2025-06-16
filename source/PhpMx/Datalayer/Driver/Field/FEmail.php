<?php

namespace PhpMx\Datalayer\Driver\Field;

/** Armazena string de email */
class FEmail extends FString
{
    /** Define um novo valor para o campo */
    function set($value)
    {
        if (is_stringable($value)) {
            $value = strtolower($value);
            $value = remove_accents($value);
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        return parent::set($value);
    }
}
