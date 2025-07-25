<?php

namespace PhpMx;

abstract class Json
{
    /** Importa o conteúdo de um arquivo json para um array */
    static function import(string $path): ?array
    {
        $path = File::setEx($path, 'json');

        $content = Import::content($path);
        $content = is_json($content) ? json_decode($content, true) : [];

        return $content;
    }

    /** Exporta um array para um arquivo json */
    static function export(string $path, array $array, bool $merge = false): void
    {
        $path = File::setEx($path, 'json');

        if ($merge) $array = [...self::import($path), ...$array];

        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        File::create($path, $json, true);
    }
}
