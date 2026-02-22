<?php

use PhpMx\Trait\TerminalMigrationTrait;

/** Reverte a última migration executada no banco de dados especificado */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        self::down($dbName);
    }
};
