<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena dados Booleanos 1 ou 0 */
class FBoolean extends Field
{
    protected function __formatExternalValue($value)
    {
        if (is_null($value) && $this->NULLABLE)
            return $value;

        return boolval($value);
    }

    protected function __formatInternalValue($value)
    {
        if (is_null($value) && $this->NULLABLE)
            return $value;

        return intval(boolval($value));
    }
}
