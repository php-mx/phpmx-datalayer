<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena numeros com casas decimais */
class FFloat extends Field
{
    protected function __formatValueToExternalUse($value) {}

    protected function __formatValueToInternalUse($value) {}
}
