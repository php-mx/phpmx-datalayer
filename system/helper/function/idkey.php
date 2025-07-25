<?php

use PhpMx\Cif;

if (!function_exists('idKeyType')) {

    /** Retorna o tipo de um idKey */
    function idKeyType(string $idKey): ?string
    {
        try {
            return Cif::off($idKey)[0];
        } catch (Throwable) {
            return null;
        }
    }
}


if (!function_exists('idKeyId')) {

    /** Retorna o id de um idKey */
    function idKeyId(string $idKey): ?int
    {
        try {
            return Cif::off($idKey)[1];
        } catch (Throwable) {
            return null;
        }
    }
}
