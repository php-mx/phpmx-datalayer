<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena linhas de Log em forma de JSON */
class FLog extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_json($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = [];

        return parent::set($value);
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue(bool $validate = false)
    {
        $value = parent::__internalValue();

        $value = json_encode($value);

        if ($validate) $this->validade($value);

        return $value;
    }

    /** Adiciona uma linha ao log */
    function add($line): static
    {
        $this->VALUE[] = [time(), $line];

        return $this;
    }
}
