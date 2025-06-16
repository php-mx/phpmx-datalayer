<?php

namespace PhpMxOld\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um hash MD5 */
class FHash extends Field
{
    protected function _formatToUse($value)
    {
        if (!is_string($value))
            $value  = serialize($value);

        if (!is_md5($value))
            $value = md5($value);

        return $value;
    }

    protected function _formatToInsert($value)
    {
        return $this->_formatToUse($value);
    }

    /** Verifica se uma variavel tem o Hash do valor do campo */
    function check($var): bool
    {
        return $this->_formatToUse($var) === $this->get();
    }
}
