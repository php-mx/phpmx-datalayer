<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um instante em forma de inteiro */
class FTime extends Field
{
    protected function __formatExternalValue($value)
    {
        if (is_bool($value))
            $value = $value ? time() : 0;

        if (is_string($value))
            $value = ctype_digit($value) ? intval($value) : strtotime(str_replace('/', '-', $value));

        $value = is_numeric($value) ? intval($value) : null;

        return $value;
    }

    protected function __formatInternalValue($value)
    {
        if (is_null($value) && $this->NULLABLE)
            return $value;

        return intval(boolval($value));
    }
}
