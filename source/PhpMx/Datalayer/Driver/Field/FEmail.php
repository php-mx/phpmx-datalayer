<?php

namespace PhpMx\Datalayer\Driver\Field;

/** Armazena string de email */
class FEmail extends FString
{
    protected function _formatToUse($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
            $value = remove_accents($value);
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
            $value = parent::_formatToUse($value);
        } else {
            $value = null;
        }

        return $value;
    }
}
