<?php

use PhpMx\Datalayer\MigrationTerminalTrait;
use PhpMx\Terminal;

return new class extends Terminal {

    use MigrationTerminalTrait;

    function __invoke($dbName = 'main')
    {
        self::up($dbName);
    }
};
