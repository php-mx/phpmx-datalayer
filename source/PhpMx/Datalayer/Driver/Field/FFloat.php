<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

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
