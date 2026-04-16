<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo JSON, com conversão automática entre array e string JSON para armazenamento e uso no sistema. */
class FJson extends Field
{
    /**
     * Define o valor JSON do campo. Strings são decodificadas para array; não-arrays são convertidos para null.
     * @param mixed $value Valor a definir (array ou string JSON).
     * @return static
     */
    function set($value): static
    {
        if (is_string($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = null;

        return parent::set($value);
    }

    /**
     * Retorna o valor codificado como string JSON para persistência no banco de dados.
     * @param bool $validate Se verdadeiro valida o valor antes de retornar.
     * @return mixed
     */
    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        if (!is_null($value))
            $value = json_encode($value);

        if ($validate) $this->validade($value);

        return $value;
    }
}
