<?php

use PhpMx\Cif;

if (!function_exists('idKeyType')) {

    /**
     * Retorna o tipo de um idKey (nome da tabela associada ao registro).
     * @param string $idKey IdKey a ser decodificado.
     * @return string|null
     */
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

    /**
     * Retorna o id numérico de um idKey.
     * @param string $idKey IdKey a ser decodificado.
     * @return int|null
     */
    function idKeyId(string $idKey): ?int
    {
        try {
            return Cif::off($idKey)[1];
        } catch (Throwable) {
            return null;
        }
    }
}
