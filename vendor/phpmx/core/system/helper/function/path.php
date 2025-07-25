<?php

use PhpMx\Path;

if (!function_exists('path')) {

    /** Formata um caminho de diretório */
    function path(): string
    {
        return Path::format(...func_get_args());
    }
}
