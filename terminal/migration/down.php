<?php

use PhpMx\Datalayer\MigrationTerminal;
use PhpMx\Terminal;

return new class extends Terminal {

    use MigrationTerminal;

    function __invoke($dbName = null)
    {
        self::down($dbName);
    }
};
