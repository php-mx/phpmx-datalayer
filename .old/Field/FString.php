<?php

namespace PhpMxOld\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use Error;

/** Armazena uma variavel em forma de string */
class FString extends Field
{
    protected $DEFAULT = '';

    protected $SIZE = 0;

    protected $CROP = true;

    protected function _formatToUse($value)
    {
        if (is_stringable($value) || is_numeric($value)) {
            $value = strval($value);
            if ($this->CROP && $this->SIZE)
                $value = substr($value, 0, $this->SIZE);
            $value = trim($value);
        } else {
            $value = null;
        }

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        if ($this->SIZE && strlen($value) > $this->SIZE)
            if ($this->CROP) {
                $value = substr($value, 0, $this->SIZE);
            } else {
                throw new Error("Value too long. Caracters accepted [$this->SIZE]. Caracters received [" . strlen($value) . "]");
            }


        return $value;
    }

    /** Determina o numero maximo de caracters do campo */
    function size(int $value): static
    {
        $this->SIZE = num_positive(intval($value));
        return $this;
    }

    /** Determina se o valor do campo deve ser cortado para caber no espaço size */
    function crop(bool $status): static
    {
        $this->CROP = $status;
        return $this;
    }
}
