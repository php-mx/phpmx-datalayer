<?php

use PhpMx\Cif;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($content)
    {
        $content = implode(' ', func_get_args());

        self::echo(Cif::on($content));
    }
};
