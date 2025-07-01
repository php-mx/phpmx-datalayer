<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;
use PhpMx\Datalayer\Driver\Field;

/** Armazena uma variavel em forma de string */
class FString extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (is_stringable($value)) {
            $size = $this->SETTINGS['size'] ?? 50;
            $crop = $this->SETTINGS['crop'] ?? false;

            if ($crop && $size)
                $value = substr($value, 0, $size);

            $value = trim($value);
        }

        return parent::set($value);
    }

    function __toString()
    {
        return strval($this->get());
    }
}
