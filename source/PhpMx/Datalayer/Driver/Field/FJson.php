<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena campo com um valor JSON */
class FJson extends Field
{
    protected $DEFAULT = [];

    protected function _formatToUse($value)
    {
        if (is_json($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = [];

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        $value = json_encode($value);

        return  $value;
    }
}
