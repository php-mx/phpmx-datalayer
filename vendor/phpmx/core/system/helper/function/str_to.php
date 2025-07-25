<?php

if (!function_exists('strToCamelCase')) {

    /** Converte uma string para camelCase */
    function strToCamelCase(string $str): string
    {
        $str = remove_accents($str);
        $str = preg_replace('/[^a-zA-Z0-9]+/', ' ', $str);
        $str = preg_split('/(?<=[a-z0-9])(?=[A-Z])|\s+/', $str);
        $str = array_filter(array_map(fn($v) => ucfirst(strtolower(trim($v))), $str), fn($v) => !is_blank($v));
        $str = implode('', $str);
        $str = lcfirst($str);
        return $str;
    }
}

if (!function_exists('strToKebabCase')) {

    /** Converte uma string para kebabCase */
    function strToKebabCase(string $str): string
    {
        $str = remove_accents($str);
        $str = preg_replace('/[^a-zA-Z0-9]+/', ' ', $str);
        $str = preg_split('/(?<=[a-z0-9])(?=[A-Z])|\s+/', $str);
        $str = array_filter(array_map(fn($v) => strtolower(trim($v)), $str), fn($v) => !is_blank($v));
        $str = implode('-', $str);
        return $str;
    }
}

if (!function_exists('strToPascalCase')) {

    /** Converte uma string para pascalCase */
    function strToPascalCase(string $str): string
    {
        $str = remove_accents($str);
        $str = preg_replace('/[^a-zA-Z0-9]+/', ' ', $str);
        $str = preg_split('/(?<=[a-z0-9])(?=[A-Z])|\s+/', $str);
        $str = array_filter(array_map(fn($v) => ucfirst(strtolower(trim($v))), $str), fn($v) => !is_blank($v));
        $str = implode('', $str);
        return $str;
    }
}

if (!function_exists('strToSnakeCase')) {

    /** Converte uma string para snakeCase */
    function strToSnakeCase(string $str): string
    {
        $str = remove_accents($str);
        $str = preg_replace('/[^a-zA-Z0-9]+/', ' ', $str);
        $str = preg_split('/(?<=[a-z0-9])(?=[A-Z])|\s+/', $str);
        $str = array_filter(array_map(fn($v) => strtolower(trim($v)), $str), fn($v) => !is_blank($v));
        $str = implode('_', $str);
        return $str;
    }
}
