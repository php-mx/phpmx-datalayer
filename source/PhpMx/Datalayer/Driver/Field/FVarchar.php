<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;
use PhpMx\Datalayer\Driver\Field;

class FVarchar extends Field
{
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strval($value);

            if ($this->SETTINGS['crop'] ?? false)
                $value = substr($value, 0, $this->SETTINGS['size']);

            $value = trim($value);
        }
        return parent::set($value);
    }

    protected function validade(mixed $value): void
    {
        parent::validade($value);

        if (!is_null($value) && strlen($value) > $this->SETTINGS['size'])
            throw new Exception("Value exceeds maximum size [{$this->SETTINGS['size']}] in [$this->NAME]");
    }
}
