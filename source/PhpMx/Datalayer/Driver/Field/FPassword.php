<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de senha (PASSWORD), com hash automático via bcrypt e verificação de valor. */
class FPassword extends Field
{
    /**
     * Define a senha do campo gerando hash bcrypt automaticamente se o valor não for já um hash.
     * @param mixed $value Valor a definir (texto simples ou hash bcrypt).
     * @return static
     */
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strval($value);
            if (!is_password($value))
                $value = password_hash($value, PASSWORD_BCRYPT);
        }

        return parent::set($value);
    }

    /**
     * Verifica se um valor corresponde ao hash de senha armazenado.
     * @param mixed $value Valor a comparar.
     * @return bool
     */
    function compare($value): bool
    {
        if (is_null($this->VALUE) || is_null($value))
            return false;

        return password_verify(strval($value), $this->VALUE);
    }
}
