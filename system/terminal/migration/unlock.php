<?php

use PhpMx\Trait\TerminalMigrationTrait;

/** Remove o nível de trava mais alto das migrations aplicadas no banco de dados */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        self::unlock($dbName);
    }
};
