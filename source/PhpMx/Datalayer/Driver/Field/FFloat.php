<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena numeros com casas decimais */
class FFloat extends Field
{
    /** Define um novo valor para o campo */
    function set($value)
    {
        if (is_numeric($value)) {
            $min = $this->SETTINS['min'] ?? $value;
            $max = $this->SETTINS['max'] ?? $value;
            $decimal = $this->SETTINS['decimal'] ?? 2;
            $round = $this->SETTINS['roud'] ?? 0;

            $value = num_interval($value, $min, $max);
            $value = num_format($value, $decimal, $round);
        } else {
            $value = null;
        }

        return parent::set($value);
    }
}
