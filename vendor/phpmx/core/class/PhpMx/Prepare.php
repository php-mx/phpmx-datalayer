<?php

namespace PhpMx;

abstract class Prepare
{
    /** Prepara um texto para ser exibido subistituindo ocorrencias do template */
    static function prepare(?string $string, array|string $prepare = []): string
    {
        if (!empty($prepare)) {
            $string = strval($string ?? '');
            if (!is_blank($string)) {
                $tags = self::getPrepareTags($string);
                if (!empty($tags)) {
                    $prepare = self::combinePrepare($prepare);
                    $string = self::resolve($string, $tags, $prepare);
                }
            }
        }
        return $string;
    }

    /** Retorna as tags prepare existentes em uma string */
    static function tags($string)
    {
        $tags = self::getPrepareTags($string);
        $tags = array_map(fn($v) => substr($v, 2, -1), $tags);
        return array_unique($tags);
    }

    /** Retorna as chaves disponiveis em um array de prepare */
    static function keys($prepare)
    {
        $keys = self::combinePrepare($prepare);
        return array_keys($keys);
    }

    /** Aplica o prepare em uma string de texto */
    protected static function resolve($string, $tags, $prepare)
    {
        list($ppN, $ppR) = self::separePrepare($prepare);

        foreach ($tags as $tag) {

            $tag = substr($tag, 1, -1);

            $value = self::getTagValue($tag, $ppN, $ppR) ?? "[%$tag]";

            $string = str_replace_first("[$tag]", $value, $string);
        }

        $string = str_replace("[%#", '[#', $string);
        $string = str_replace('\#', "&#35", $string);

        return $string;
    }

    /** Retorna um valor para ser usado em um prepare */
    protected static function getTagValue($tag, &$ppN, $ppR, bool $runClosure = true)
    {
        if ($tag == '#') {

            $value = array_shift($ppN) ?? null;

            if ($runClosure && is_closure($value))
                $value = $value();

            return $value;
        }

        if (strpos($tag, ':') === false) {
            $tag = substr($tag, 1);

            $value = $ppR[$tag] ?? null;

            if ($runClosure && is_closure($value))
                $value = $value();

            return $value;
        } else {

            $paramns = explode(":", $tag);

            $function = array_shift($paramns);

            $paramns = implode(":", $paramns);
            $paramns = explode(",", $paramns);

            $function = self::getTagValue($function, $ppN, $ppR, false);

            if (is_closure($function)) {
                foreach ($paramns as &$param) {
                    if (intval($param) == $param) {
                        $param = intval($param);
                    } else if (strtolower($param) == 'false') {
                        $param = false;
                    } else if (strtolower($param) == 'true') {
                        $param = true;
                    } else {
                        $param = str_replace('\#', "&#35", $param);
                        $param = self::getTagValue($param, $ppN, $ppR) ?? $param;
                    }
                }
                return $function(...$paramns);
            }

            return null;
        }

        return $tag;
    }

    /** Repara as tags sequenciais  das tags referenciadas */
    protected static function separePrepare($prepare): array
    {
        $sequence = [];
        $reference = [];
        foreach ($prepare as $key => $value) {
            if (is_numeric($key)) {
                $sequence[] = $value;
            } else {
                $reference[$key] = $value;
            }
        }
        return [$sequence, $reference];
    }

    /** Combina subarray de prepare em um prepare de array unico */
    protected static function combinePrepare(array|string $prepare): array
    {
        $prepare = is_array($prepare) ? $prepare : [$prepare];
        foreach ($prepare as $key => $value) {
            if (is_array($value)) {
                $prepare[$key] = json_encode($value);
                foreach (self::combinePrepare($value) as $subKey => $subValue) {
                    $newKey = $subKey == '.' ? $key : "$key.$subKey";
                    $prepare[$newKey] = $subValue;
                }
            }
        }
        return $prepare;
    }

    /** Retorna os comandos prepare existentes dentro da string */
    protected static function getPrepareTags(string $string): array
    {
        preg_match_all("#\[[\#\>][^\]]*+\]#i", $string, $tags);
        $tags = array_shift($tags);
        return $tags;
    }

    /** Escapa as tags prepare de um texto */
    static function scape($string, ?array $prepare = null): string
    {
        if ($prepare) {
            $prepare = self::combinePrepare($prepare);
            $prepare = array_keys($prepare);

            $replace = array_map(fn($value) => "[&#35$value]", $prepare);
            $prepare = array_map(fn($value) => "[#$value]", $prepare);

            return str_replace($prepare, $replace, $string);
        } else {
            return str_replace('[#', "[&#35", $string);
        }
    }
}
