<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;

/** Campo de e-mail, com sanitização, normalização e validação de formato automáticas. */
class FEmail extends FVarchar
{
    /**
     * Define o valor do campo normalizando para minúsculas, removendo acentos e sanitizando o e-mail.
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strtolower(strval($value));
            $value = remove_accents($value);
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        return parent::set($value);
    }

    /**
     * Valida se o valor é um endereço de e-mail válido.
     * @param mixed $value Valor a validar.
     * @throws \Exception
     */
    protected function validade(mixed $value): void
    {
        parent::validade($value);

        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL))
            throw new Exception("The value is not a valid email [$this->NAME]");
    }
}
