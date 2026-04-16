<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Campo inteiro (INT), com suporte a valor mínimo, máximo e arredondamento configuráveis. */
class FInt extends Field
{
    /**
     * Define o valor inteiro do campo, aplicando intervalo min/max e arredondamento configurados.
     * Valores não numéricos são convertidos para null.
     * @param mixed $value Valor a definir.
     * @return static
     */
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
