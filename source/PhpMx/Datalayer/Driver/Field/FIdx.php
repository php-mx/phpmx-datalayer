<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena um ID de referencia para uma tabela */
class FIdx extends Field
{
    protected function __formatValueToExternalUse($value) {}

    protected function __formatValueToInternalUse($value) {}
}
