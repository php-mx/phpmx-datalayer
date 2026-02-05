<?php

use PhpMx\Datalayer\MigrationTerminalTrait;

/** Reverte todas as migrations executadas no banco de dados, retornando-o ao estado inicial */
return new class {

    use MigrationTerminalTrait;

    function __invoke($dbName = 'main')
    {
        while (self::down($dbName));
    }
};
