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

    /** Define um valor do campo */
    function set($value): static
    {
        $this->VALUE = $this->__formatExternalValue($value);
        return $this;
    }

    /** Retorna o valor do campo */
    function get()
    {
        return $this->VALUE;
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __getInsertValue()
    {
        return $this->__formatInternalValue($this->VALUE);
    }

    abstract protected function __formatExternalValue($value);
    abstract protected function __formatInternalValue($value);
}
