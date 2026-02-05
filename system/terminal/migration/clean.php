<?php

use PhpMx\Trait\TerminalMigrationTrait;

/** Reverte todas as migrations executadas no banco de dados, retornando-o ao estado inicial */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        while (self::down($dbName));
    }
};
