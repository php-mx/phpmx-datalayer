<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena uma variavel em forma de texto livre */
class FText extends Field
{
    protected function __formatValueToExternalUse($value) {}

    protected function __formatValueToInternalUse($value) {}
}
