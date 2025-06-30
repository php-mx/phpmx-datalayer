<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;

/** Armazena string de email */
class FEmail extends FString
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_stringable($value)) {
            $value = strtolower($value);
            $value = remove_accents($value);
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        return parent::set($value);
    }

    /** Verifica se o campo pode ser insetido no banco de dados */
    protected function validade(mixed $value): void
    {
        parent::validade($value);

        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL))
            throw new Exception("The value is not a valid email [$this->NAME]");
    }

    function __toString()
    {
        return $this->get();
    }
}
