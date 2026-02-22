<?php

use PhpMx\Trait\TerminalMigrationTrait;

/** Trava todas as migrations executadas em um novo nível de proteção */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        self::lock($dbName);
    }
};
