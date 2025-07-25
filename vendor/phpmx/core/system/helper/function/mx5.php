<?php

use PhpMx\Mx5;

if (!function_exists('mx5')) {

    /** Retorna o mx5 de uma variável */
    function mx5(mixed $var): mixed
    {
        return Mx5::on($var);
    }
}
