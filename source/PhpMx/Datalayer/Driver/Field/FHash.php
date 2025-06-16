<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um hash MD5 */
class FHash extends Field
{
    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return parent::set($value);
    }

    /** Verifica se  baum valor bate com o valor do campo */
    function compare($value): bool
    {
        if (!is_null($value))
            $value = is_md5($value) ? $value : md5($value);

        return $value == $this->VALUE;
    }
}
