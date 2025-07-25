<?php

if (!function_exists('remove_accents')) {

    /** Remove a acentuação de uma string */
    function remove_accents(string $string): string
    {
        return strtr($string, CHAR_NORMALIZER);
    }
}
