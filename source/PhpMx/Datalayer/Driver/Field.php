<?php

namespace PhpMx\Datalayer\Driver;

abstract class Field
{
    protected mixed $VALUE = null;
    protected array $SETTINS = [];
    protected mixed $DEFAULT = null;
    protected bool $NULLABLE = false;

    final function __construct(bool $nullable, mixed $default, array $settings)
    {
        $this->NULLABLE = $nullable;
        $this->DEFAULT = $default;
        $this->SETTINS = $settings;
        $this->set($this->DEFAULT);
    }

    /** Define um novo valor para o campo */
    function set($value)
    {
        if (!$this->NULLABLE && is_null($value))
            $value = $this->DEFAULT;

        $this->VALUE = $value;
    }

    /** Retorna o valor do campo para ser usado no sistema */
    function get()
    {
        return $this->VALUE;
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue()
    {
        return $this->VALUE;
    }
}
