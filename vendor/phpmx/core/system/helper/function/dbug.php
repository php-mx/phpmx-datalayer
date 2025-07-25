<?php

if (!function_exists('dbug')) {

    /** Realiza o var_dump de variaveis */
    function dbug(mixed ...$params): void
    {
        ini_set('xdebug.var_display_max_depth', '10');
        ini_set('xdebug.var_display_max_children', '256');
        ini_set('xdebug.var_display_max_data', '1024');

        foreach ($params as $param)
            var_dump($param);
    }
}

if (!function_exists('dbugpre')) {

    /** Realiza o var_dump de variaveis dentro de uma tag HTML pre */
    function dbugpre(mixed ...$params): void
    {
        echo '<pre/>';
        dbug(...$params);
        echo '<pre/>';
    }
}

if (!function_exists('dd')) {

    /** Realiza o var_dump de variaveis finalizando o sistema */
    function dd(mixed ...$params): never
    {
        dbug(...$params);
        die;
    }
}

if (!function_exists('ddpre')) {

    /** Realiza o var_dump de variaveis dentro de uma tag HTML pre finalizando o sistema */
    function ddpre(mixed ...$params): never
    {
        dbugpre(...$params);
        die;
    }
}
