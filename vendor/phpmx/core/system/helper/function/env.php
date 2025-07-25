<?php

use PhpMx\Env;

if (!function_exists('env')) {

    /** Recupera o valor de uma variavel de ambiente */
    function env(string $name): mixed
    {
        return Env::get($name) ?? null;
    }
}
