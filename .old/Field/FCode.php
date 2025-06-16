<?php

namespace PhpMxOld\Datalayer\Driver\Field;

use PhpMx\Code;
use PhpMx\Datalayer\Driver\Field;

/** Armazena um hash Code */
class FCode extends Field
{
    protected function _formatToUse($value)
    {
        if (!is_string($value))
            $value  = serialize($value);

        $value = Code::on($value);

        return $value;
    }

    protected function _formatToInsert($value)
    {
        return $this->_formatToUse($value);
    }

    /** Verifica se uma variavel tem o Hash do valor do campo */
    function check($var): bool
    {
        return Code::compare($this->_formatToUse($var), $this->get());
    }
}
