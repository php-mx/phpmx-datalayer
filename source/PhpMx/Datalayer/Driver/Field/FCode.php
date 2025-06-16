<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Code;
use PhpMx\Datalayer\Driver\Field;

/** Armazena um hash Code */
class FCode extends Field
{
    /** Define um novo valor para o campo */
    function set($value)
    {
        if (!is_null($value))
            $value = Code::check($value) ? $value : Code::on($value);

        parent::set($value);
    }
}
