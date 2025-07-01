<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Code;
use PhpMx\Datalayer\Driver\Field;

/** Armazena um hash Code */
class FCode extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        $value = is_null($value) ? $value : Code::on($value);

        return parent::set($value);
    }

    /** Verifica se  baum valor bate com o valor do campo */
    function compare($value): bool
    {
        return Code::compare(Code::on($value), $this->get());
    }

    function __toString()
    {
        return strval($this->get());
    }
}
