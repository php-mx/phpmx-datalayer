<?php

namespace PhpMx;

abstract class Mx5
{
    protected static ?array $KEY = null;
    protected static array $HEX_CHARS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'a', 'b', 'c', 'd', 'e', 'f'];
    protected static array $MX_CHARS  = ['s', 'i', 'q', 'j', 'n', 'g', 'p', 'l', 'v', 'o', 'u', 'y', 't', 'w', 'r', 'h'];

    /** Retorna o mx5 de uma variável */
    static function on(mixed $var): string
    {
        if (!self::check($var)) {
            if (!is_md5($var)) $var = md5(is_stringable($var) ? "$var" : serialize($var));
            $var = str_replace(self::$HEX_CHARS, self::loadKey(), $var);
            $var = "m{$var}x";
        }

        return $var;
    }

    /** Retonra o md5 usado para gerar um mx5 */
    static function off(mixed $var): string
    {
        if (!self::check($var))
            return self::off(self::on($var));

        $var = str_replace(self::loadKey(), self::$HEX_CHARS, substr($var, 1, -1));

        return $var;
    }

    /** Verifica se uma variavel é um mx5 */
    static function check(mixed $var): bool
    {
        return is_string($var)
            && strlen($var) === 34
            && strtolower($var) === $var
            && $var[0] === 'm'
            && $var[33] === 'x'
            && strspn(substr($var, 1, 32), implode('', self::$MX_CHARS)) === 32;
    }

    /** Verifica se todas as strings tem o mesmo mx5 */
    static function compare(mixed $initial, mixed ...$compare): bool
    {
        $initial = self::off($initial);

        foreach ($compare as $item)
            if ($initial != self::off($item))
                return false;

        return true;
    }

    private static function loadKey(): array
    {
        if (is_null(self::$KEY)) {
            $key = env('mx5_KEY');
            $key = md5($key);
            $key = str_replace(self::$HEX_CHARS,  self::$MX_CHARS, $key);
            $key .= implode('', self::$MX_CHARS);
            $key = array_keys(array_flip(str_split(strrev($key))));
            self::$KEY = $key;
        }

        return self::$KEY;
    }
}
