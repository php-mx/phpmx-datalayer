<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo hash MD5, com conversão automática do valor e verificação de igualdade. */
class FMd5 extends Field
{
    /**
     * Define o valor do campo convertendo-o para hash MD5 se ainda não estiver no formato correto.
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return parent::set($value);
    }

    /**
     * Verifica se um valor corresponde ao hash MD5 armazenado.
     * @param mixed $value Valor a comparar (convertido para MD5 automaticamente se necessário).
     * @return bool
     */
    function compare($value): bool
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return $value == $this->VALUE;
    }
}
