<?php

use PhpMx\Cif;

if (!function_exists('is_idKey')) {

    /** Verifica se uma variavel é um idKey */
    function is_idKey(mixed $idKey): bool
    {
        if (Cif::check($idKey)) {
            $idKey = Cif::off($idKey);
            if (is_array($idKey) && is_string(array_shift($idKey)) && is_int(array_shift($idKey)))
                return true;
        }
        return false;
    }
}

if (!function_exists('idKeyType')) {

    /** Retorna o tipo de um idKey */
    function idKeyType(string $idKey): ?string
    {
        try {
            return Cif::off($idKey)[0];
        } catch (Error | Exception) {
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
        } catch (Error | Exception) {
            return null;
        }
    }
}
