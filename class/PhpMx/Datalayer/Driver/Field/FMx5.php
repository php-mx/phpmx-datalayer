<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use PhpMx\Mx5;

/** Armazena um mx5 */
class FMx5 extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        $value = is_null($value) ? $value : mx5($value);

        return parent::set($value);
    }

    /** Verifica se  baum valor bate com o valor do campo */
    function compare($value): bool
    {
        return Mx5::compare($value, $this->get());
    }
}
