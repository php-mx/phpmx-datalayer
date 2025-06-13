<?php

namespace PhpMx\Datalayer\Driver;

abstract class Field
{
    protected $VALUE;
    protected $DEFAULT = null;
    protected $USE_NULL = null;

    function __construct(?bool $useNull = null, mixed $default = null)
    {
        $this->USE_NULL = $this->USE_NULL ?? $useNull ?? false;

        if (!$this->USE_NULL)
            $default = $default ?? $this->DEFAULT;

        $this->DEFAULT = $default;

        $this->set($this->DEFAULT);
    }

    /** Define um valor do campo */
    function set($value): static
    {
        $this->VALUE = $this->_useValue($value);
        return $this;
    }

    /** Retorna o valor do campo */
    function get()
    {
        return $this->_useValue($this->VALUE);
    }

    /** Retorna o campo formatado para ser inserido no banco de dados */
    function _insert()
    {
        $value = $this->get();
        return is_null($value) ? null : $this->_formatToInsert($value);
    }

    /** Define o valor que deve ser utilizado */
    final protected function _useValue($value)
    {
        if (is_null($value))
            return $this->USE_NULL ? null : $this->DEFAULT;

        return $this->_formatToUse($value);
    }

    abstract protected function _formatToUse($value);
    abstract protected function _formatToInsert($value);
}
