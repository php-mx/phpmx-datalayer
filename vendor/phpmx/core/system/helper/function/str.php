<?php

if (!function_exists('str_var')) {

    /** Extrai uma variavel de dentro de uma string */
    function str_get_var($var): mixed
    {
        if (!is_string($var))
            return $var;

        if ($var === 'null' || $var === 'NULL' || $var === '')
            return null;

        if ($var == 'true' || $var === 'TRUE')
            return true;

        if ($var === 'false' || $var === 'FALSE')
            return false;

        if (strval(intval($var)) === $var)
            return intval($var);

        if (strval(floatval($var)) === $var)
            return floatval($var);

        return $var;
    }
}

if (!function_exists('str_replace_all')) {

    /** Substitua todas as ocorrências da string de pesquisa pela string de substituição */
    function str_replace_all(array|string $search, array|string $replace, string $subject, int $loop = 10): string
    {
        $count = 0;
        $subject = str_replace($search, $replace, $subject, $count);
        while ($loop && $count) {
            $subject = str_replace($search, $replace, $subject, $count);
            $loop--;
        }
        return $subject;
    }
}

if (!function_exists('str_replace_first')) {

    /** Substitua a primeira ocorrência da string de pesquisa pela string de substituição */
    function str_replace_first(array|string $search, array|string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
}

if (!function_exists('str_replace_last')) {

    /** Substitua a ultima ocorrência da string de pesquisa pela string de substituição */
    function str_replace_last(array|string $search, array|string $replace, string $subject): string
    {
        $pos = strrpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
}

if (!function_exists('str_trim')) {

    /** Tira o espaço em branco (ou outros caracteres) do início e do fim de uma substring dentro de uma string */
    function str_trim(string $string, array|string $substring, array|string $characters = " \t\n\r\0\x0B"): string
    {
        $charactersArray = [];
        $substringArray = [];

        $characters = is_array($characters) ? $characters : [$characters];
        $substring = is_array($substring) ? $substring : [$substring];

        foreach ($substring as $vs)
            foreach ($characters as $vt) {
                $charactersArray[] = "$vs$vt";
                $charactersArray[] = "$vt$vs";
                $substringArray[] = $vs;
                $substringArray[] = $vs;
            }

        $string = mb_str_replace_all($charactersArray, $substringArray, $string);

        return $string;
    }
}

if (!function_exists('mb_str_replace')) {

    /** Substitua ocorrências da string de pesquisa pela string de substituição */
    function mb_str_replace(array|string $search, array|string $replace, string $subject, &$count = 0): string
    {
        if (!is_array($subject)) {
            $searches = is_array($search) ? array_values($search) : array($search);
            $replacements = is_array($replace) ? array_values($replace) : array($replace);
            $replacements = array_pad($replacements, count($searches), '');
            foreach ($searches as $key => $search) {
                $parts = mb_split(preg_quote($search), $subject);
                $count += count($parts) - 1;
                $subject = implode($replacements[$key], $parts);
            }
        } else {
            foreach ($subject as $key => $value)
                $subject[$key] = mb_str_replace($search, $replace, $value, $count);
        }
        return $subject;
    }
}

if (!function_exists('mb_str_replace_all')) {

    /** Substitui todas as ocorrências da string de procura com a string de substituição */
    function mb_str_replace_all(array|string $search, array|string $replace, string $subject, int $loop = 10): string
    {
        $pre = $subject;
        $subject = mb_str_replace($search, $replace, $subject);
        while ($loop && $pre != $subject) {
            $pre = $subject;
            $subject = mb_str_replace($search, $replace, $subject);
            $loop--;
        }
        return $subject;
    }
}

if (!function_exists('mb_str_split')) {

    /** Converte uma string em um array */
    function mb_str_split(string $string, int $string_length = 1): array
    {
        if (mb_strlen($string) > $string_length || !$string_length) {
            do {
                $parts[] = mb_substr($string, 0, $string_length);
                $string = mb_substr($string, $string_length);
            } while (!empty($string));
        } else {
            $parts = array($string);
        }
        return $parts;
    }
}
