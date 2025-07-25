<?php

use PhpMx\Prepare;

if (!function_exists('prepare')) {

    /** Prepara um texto para ser exibido subistituindo ocorrencias do template */
    function prepare(?string $string, array|string $prepare = []): string
    {
        return Prepare::prepare($string ?? '', $prepare);
    }
}
