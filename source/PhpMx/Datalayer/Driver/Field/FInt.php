<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use Error;

/** Armazena numeros inteiros */
class FInt extends Field
{
    protected $DEFAULT = 0;

    protected $MIN;
    protected $MAX;
    protected $SIZE;
    protected $ROUND = 0;

    protected function _formatToUse($value)
    {
        if (is_numeric($value)) {
            $min = $this->MIN ?? $value;
            $max = $this->MAX ?? $value;
            $value = num_interval($value, $min, $max);
            $value = num_round($value, $this->ROUND);
        } else {
            $value = null;
        }

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        if ($this->SIZE && strlen($value) > $this->SIZE)
            throw new Error("Value too long. Caracters accepted [$this->SIZE]. Caracters received [" . strlen($value) . "]");

        return $value;
    }

    /** Determina o numero maximo de caracters do campo */
    function size(?int $value): static
    {
        $this->SIZE = $value;
        return $this;
    }

    /** Termina valor maximo do campo */
    function max(?int $value): static
    {
        $this->MAX = $value;
        return $this;
    }

    /** Termina valor minimo do campo */
    function min(?int $value): static
    {
        $this->MIN = $value;
        return $this;
    }

    /** Termina a forma de arredondamento do campo */
    function round(?int $value): static
    {
        $this->ROUND = num_interval(intval($value), -1, 1);
        return $this;
    }

    /** Soma um valor numerico ao valor do campo */
    function sum(int $value): static
    {
        $currentValue = $this->get() ?? 0;
        $this->set($value + $currentValue);
        return $this;
    }
}
