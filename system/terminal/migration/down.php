<?php

use PhpMx\Datalayer\MigrationTerminalTrait;

/** Reverte a última migration executada no banco de dados especificado */
return new class {

    use MigrationTerminalTrait;

    function __invoke($dbName = 'main')
    {
        self::down($dbName);
    }
};
