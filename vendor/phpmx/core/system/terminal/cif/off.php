<?php

use PhpMx\Cif;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($cif)
    {
        self::echo(Cif::off($cif));
    }
};
