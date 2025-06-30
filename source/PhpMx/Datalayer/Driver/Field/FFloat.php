<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena numeros com casas decimais */
class FFloat extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_numeric($value)) {
            $min = $this->SETTINGS['min'] ?? $value;
            $max = $this->SETTINGS['max'] ?? $value;
            $decimal = $this->SETTINGS['decimal'] ?? 2;
            $round = $this->SETTINGS['roud'] ?? 0;

            $value = num_interval($value, $min, $max);
            $value = num_format($value, $decimal, $round);
        } else {
            $value = null;
        }

        return parent::set($value);
    }

    function __toString()
    {
        return strval($this->get());
    }
}
