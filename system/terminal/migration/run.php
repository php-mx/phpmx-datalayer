<?php

use PhpMx\Datalayer\MigrationTerminalTrait;

/** Executa todas as migrations pendentes no banco de dados até que o esquema esteja atualizado */
return new class {

    use MigrationTerminalTrait;

    function __invoke($dbName = 'main')
    {
        while (self::up($dbName));
    }
};
