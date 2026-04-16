<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo booleano (BOOLEAN), com conversão automática para inteiro ao persistir no banco de dados. */
class FBoolean extends Field
{
    /**
     * Define o valor booleano do campo, convertendo qualquer não-nulo para bool.
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        $value = is_null($value) ? null : boolval($value);

        return parent::set($value);
    }

    /**
     * Retorna o valor como inteiro (0 ou 1) para persistência no banco de dados.
     * @param bool $validate Se verdadeiro valida o valor antes de retornar.
     * @return mixed
     */
    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        if (is_bool($value))
            $value = intval($value);

        if ($validate) $this->validade($value);

        return $value;
    }
}
