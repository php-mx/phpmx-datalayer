<?php

namespace PhpMx;

use Exception;

abstract class Cif
{
    protected static array $ENSURE;
    protected static ?int $CURRENT_ID_KEY = null;
    protected static ?array $CIF = null;

    final const BASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /** Retorna a cifra de uma variavel */
    static function on(mixed $var, ?string $charKey = null): string
    {
        self::__load();

        if (
            is_string($var)
            && str_starts_with($var, '-')
            && str_ends_with($var, '-')
            && self::checkEncapsChar(substr($var, 1, -1))
        ) return $var;

        $idKey = self::getUseIdKey($charKey);

        $var = serialize($var);

        $var = base64_encode($var);

        $var = str_replace('=', '', $var);

        $var = strrev($var);

        $var = self::replace($var, self::BASE, self::$CIF[$idKey]);

        $var = self::getEncapsChar($idKey) . $var . self::getEncapsChar($idKey, true);

        $var = str_replace('/', '-', $var);
        $var = "-$var-";

        return $var;
    }

    /** Retorna a variavel de uma cifra */
    static function off(mixed $var): mixed
    {
        if (!self::check($var)) return $var;

        if (strpos($var, ' ') !== false) $var = urlencode($var);

        $key = self::getUseIdKey(substr($var, 1, 1));

        $var = substr($var, 2, -2);

        $var = str_replace('-', '/', $var);

        $var = self::replace($var, self::$CIF[$key], self::BASE);

        $var = base64_decode(strrev($var));

        if (is_serialized($var))
            $var = unserialize($var);

        return $var;
    }

    /** Verifica se uma variavel atende os requisitos para ser uma cifra */
    static function check(mixed $var): bool
    {
        return $var == self::on($var);
    }

    /** Verifica se todas as variaveis tem a mesma cifra */
    static function compare(mixed $initial, mixed ...$compare): bool
    {
        $initial = self::off($initial);

        foreach ($compare as $item)
            if ($initial != self::off($item))
                return false;

        return true;
    }

    /** Realiza o replace interno de uma string */
    protected static function replace(string $string, string $in, string $out): string
    {
        for ($i = 0; $i < strlen($string); $i++)
            if (strpos($in, $string[$i]) !== false)
                $string[$i] = $out[strpos($in, $string[$i])];

        return $string;
    }

    /** Retorna o id que deve ser utilizado */
    protected static function getUseIdKey(?string $charKey): int
    {
        self::__load();

        self::$CURRENT_ID_KEY = self::$CURRENT_ID_KEY ?? random_int(0, 61);

        if (!is_null($charKey))
            $idKey = array_flip(self::$ENSURE)[substr($charKey, 0, 1)];

        return $idKey ?? self::$CURRENT_ID_KEY;
    }

    /** Retorna o caracter de encapsulamento */
    protected static function getEncapsChar(int $idKey, bool $reverse = false): string
    {
        if ($reverse) $idKey = 61 - $idKey;
        $charKey = self::$ENSURE[$idKey] ?? 0;
        return $charKey;
    }

    /** Verifica os caracteres de encapsulamento de uma string */
    protected static function checkEncapsChar(string $string)
    {
        $idCharKeyStart = self::getUseIdKey(substr($string, 0, 1));
        return self::getEncapsChar($idCharKeyStart, true) == substr($string, -1, 1);
    }

    /** Carrega o arquivo de certificado do projeto */
    protected static function __load()
    {
        if (is_null(self::$CIF)) {
            $path = env('CIF');

            $path = Path::seekForFile("library/certificate/$path.crt");

            if (!$path)
                $path = Path::seekForFile('library/certificate/base.crt');

            self::loadFileCif($path);
        }
    }

    /** Carrega a classe com um arquivo de certificado */
    private static function loadFileCif(string $path)
    {
        if (!File::check($path))
            throw new Exception("Cif file [$path] not found.");

        $content = Import::content($path);
        $content = str_replace([" ", "\t", "\n", "\r", "\0", "\x0B"], '', $content);
        $cif = str_split($content, 62);

        self::$ENSURE = str_split(array_pop($cif));
        self::$CIF = $cif;
    }
}
