<?php

use PhpMx\Trait\TerminalMigrationTrait;

/** Executa todas as migrations pendentes no banco de dados até que o esquema esteja atualizado */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        while (self::up($dbName));
    }
};
