<?php

use PhpMx\Datalayer\MigrationTerminalTrait;

/** Executa a próxima migration pendente no banco de dados especificado */
return new class {

    use MigrationTerminalTrait;

    function __invoke($dbName = 'main')
    {
        self::up($dbName);
    }
};
