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
