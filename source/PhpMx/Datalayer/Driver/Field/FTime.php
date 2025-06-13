<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um instante em forma de inteiro */
class FTime extends Field
{
    protected $DEFAULT = 0;

    protected function _formatToUse($value)
    {
        if (is_bool($value))
            $value = $value ? time() : 0;

        if (is_string($value))
            $value = ctype_digit($value) ? intval($value) : strtotime(str_replace('/', '-', $value));

        $value = is_numeric($value) ? intval($value) : null;

        return $value;
    }

    protected function _formatToInsert($value)
    {
        return $this->_formatToUse($value);
    }

    /** Retorna o valor do campo ou o valor de uma configação do campo */
    function get()
    {
        if (func_num_args() == 1)
            return $this->getFormated(...func_get_args());

        return parent::get();
    }

    /** Retorna o campo formatado como uma data */
    function getFormated($format): string
    {
        $value = $this->get() ?? 0;
        return date($format, $value);
    }
}
