<?php

use PhpMx\Cif;
use PhpMx\Mx5;

if (!function_exists('is_base64')) {

    /** Verifica se uma variavel é uma string base64 */
    function is_base64(mixed $var): bool
    {
        if (empty($var) || !is_string($var))
            return false;

        return base64_encode(base64_decode($var, true)) === $var;
    }
}

if (!function_exists('is_blank')) {

    /** Verifica se uma variavel é nula, vazia ou composta de espaços em branco */
    function is_blank(mixed $var): bool
    {
        if (is_string($var))
            $var = trim($var);

        return (empty($var) && !is_numeric($var) && !is_bool($var));
    }
}

if (!function_exists('is_class')) {

    /** Verifica se um objeto é ou extende uma classe */
    function is_class(mixed $object, object|string $class): bool
    {
        if (is_string($object) || is_object($object)) {
            $object = is_string($object) ? $object : $object::class;
            $class = is_string($class) ? $class : $class::class;

            if (class_exists($object) && class_exists($class))
                return $object == $class || isset(class_parents($object)[$class]);
        }

        return false;
    }
}

if (!function_exists('is_cif')) {

    /** Verifica se uma variavel atende os requisitos para ser uma cifra */
    function is_cif(mixed $var): bool
    {
        return Cif::check($var);
    }
}

if (!function_exists('is_closure')) {

    /** Verifica se uma variavel é uma função anonima ou objeto callable */
    function is_closure(mixed $var): bool
    {
        return ($var instanceof Closure) || (is_object($var) && is_callable($var));
    }
}

if (!function_exists('is_extend')) {

    /** Verifica se um objeto extende uma classe */
    function is_extend(mixed $object, object|string $class): bool
    {
        if (is_string($object) || is_object($object)) {
            $object = is_string($object) ? $object : $object::class;
            $class = is_string($class) ? $class : $class::class;

            if (class_exists($object) && class_exists($class))
                return isset(class_parents($object)[$class]);
        }

        return false;
    }
}

if (!function_exists('is_image_base64')) {

    /** Verifica se uma variavel é uma url de imagem base64 */
    function is_image_base64(mixed $var): bool
    {
        if (empty($var) || !is_string($var))
            return false;


        if (preg_match('/^data:image\/(jpeg|jpg|png|gif|bmp|webp);base64,/', $var)) {
            $data = explode(',', $var);
            if (isset($data[1]) && is_base64($data[1]))
                return true;
        }

        return false;
    }
}

if (!function_exists('is_implement')) {

    /** Verifica se um objeto implementa uma interface */
    function is_implement(mixed $object, object|string $interface): bool
    {
        if (is_string($object) || is_object($object)) {
            $object = is_string($object) ? $object : $object::class;

            if (class_exists($object) && interface_exists($interface))
                return isset(class_implements($object)[$interface]);
        }

        return false;
    }
}

if (!function_exists('is_json')) {

    /** Verifica se uma variavel é uma string JSON */
    function is_json(mixed $var): bool
    {
        if (is_string($var))
            try {
                json_decode($var);
                return json_last_error() === JSON_ERROR_NONE;
            } catch (Throwable) {
            }

        return false;
    }
}

if (!function_exists('is_md5')) {

    /** Verifica se uma variavel é md5 */
    function is_md5(mixed $var): bool
    {
        return is_string($var) ? boolval(preg_match('/^[a-fA-F0-9]{32}$/', $var)) : false;
    }
}

if (!function_exists('is_mx5')) {

    /** Verifica se uma variavel é um mx5 */
    function is_mx5(mixed $var): bool
    {
        return Mx5::check($var);
    }
}

if (!function_exists('is_serialized')) {

    /** Verifica se uma variavel corresponde uma string serializada */
    function is_serialized($var, $strict = true): bool
    {
        if (!is_string($var)) {
            return false;
        }
        $var = trim($var);
        if ('N;' === $var) {
            return true;
        }
        if (strlen($var) < 4) {
            return false;
        }
        if (':' !== $var[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($var, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($var, ';');
            $brace     = strpos($var, '}');
            if (false === $semicolon && false === $brace) {
                return false;
            }
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $var[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($var, -2, 1)) {
                        return false;
                    }
                } elseif (!str_contains($var, '"')) {
                    return false;
                }
            case 'a':
            case 'O':
            case 'E':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $var);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $var);
        }
        return false;
    }
}

if (!function_exists('is_stringable')) {

    /** Verifica se uma variavel é uma string ou algo que possa ser convertido para string */
    function is_stringable(mixed $var): bool
    {
        return is_string($var) || is_numeric($var) || ($var instanceof Stringable);
    }
}

if (!function_exists('is_trait')) {

    /** Verifica se um objeto utiliza uma trait */
    function is_trait(mixed $object, object|string|null $trait): bool
    {
        if (is_string($object) || is_object($object)) {
            $object = is_string($object) ? $object : $object::class;

            if (class_exists($object) && trait_exists($trait)) {
                if (isset(class_uses($object)[$trait]))
                    return true;

                foreach (class_parents($object) as $parrent)
                    if (isset(class_uses($parrent)[$trait]))
                        return true;
            }
        }

        return false;
    }
}
