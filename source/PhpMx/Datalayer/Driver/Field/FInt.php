<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

class FInt extends Field
{
    function set($value): static
    {
        if (is_numeric($value)) {
            $min = $this->SETTINGS['min'] ?? $value;
            $max = $this->SETTINGS['max'] ?? $value;
            $round = $this->SETTINGS['round'] ?? 0;

            $value = num_interval($value, $min, $max);
            $value = num_round($value, $round);
        } else {
            $value = null;
        }

        return parent::set($value);
    }
}
