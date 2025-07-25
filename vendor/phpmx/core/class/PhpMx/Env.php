<?php

namespace PhpMx;

abstract class Env
{
    protected static array $DEFAULT = [];

    /** Carrega variaveis de ambiente de um arquivo para o sistema */
    static function loadFile(string $filePath): bool
    {
        $filePath = path($filePath);

        if (is_file($filePath)) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') !== 0) {
                    list($name, $value) = explode('=', $line, 2);
                    self::set($name, $value);
                }
            }
            return true;
        }

        return false;
    }

    /** Define o valor de uma variavel de ambiente */
    static function set(string $name, mixed $value): void
    {
        $name = trim($name);
        $value = trim($value, " \"'");

        $value = str_get_var($value);

        if (!isset($_ENV[$name]))
            $_ENV[$name] = $_ENV[$name] ?? $value;
    }

    /** Recupera o valor de uma variavel de ambiente */
    static function get(string $name): mixed
    {
        return $_ENV[$name] ?? self::$DEFAULT[$name] ?? null;
    }

    /** Define variaveis de ambiente padrão caso não tenha sido declarada */
    static function default(string $name, mixed $value): void
    {
        $value = str_get_var($value);

        self::$DEFAULT[$name] = $value;
    }
}
