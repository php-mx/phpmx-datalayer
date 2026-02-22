<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo de ponto flutuante (FLOAT), com suporte a valor mínimo e máximo configuráveis. */
class FFloat extends Field
{
    function set($value): static
    {
        if (is_numeric($value)) {
            $min = $this->SETTINGS['min'] ?? $value;
            $max = $this->SETTINGS['max'] ?? $value;

            $value = num_interval($value, $min, $max);
        } else {
            $value = null;
        }

        return parent::set($value);
    }
}
