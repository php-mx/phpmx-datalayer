<?php

namespace PhpMx;

abstract class Dir
{
    /** Cria um diretório */
    static function create(string $path): ?bool
    {
        $path = self::getOnly($path);

        if (empty($path)) return null;

        return Log::add('dir', "create $path", function () use ($path) {
            if (!is_dir($path)) {
                $createList = explode('/', $path);
                $createPath = '';
                foreach ($createList as $creating) {
                    $createPath = ($createPath == '') ? $creating : self::getOnly("$createPath/$creating");
                    if ($createPath != '.' && $createPath != '..' && !empty($createPath) && !self::check($createPath))
                        mkdir($createPath);
                }
                return is_dir($path);
            }
            return null;
        });
    }

    /** Remove um diretório */
    static function remove(string $path, bool $recursive = false): ?bool
    {
        $path = self::getOnly($path);

        return Log::add('dir', "remove $path", function () use ($path, $recursive) {
            if (is_dir($path)) {
                if ($recursive || empty(self::seekForAll($path))) {
                    $drop = function ($path, $function) {
                        foreach (scandir($path) as $item)
                            if ($item != '.' && $item != '..')
                                if (is_dir("$path/$item")) {
                                    $function("$path/$item", $function);
                                } else {
                                    unlink("$path/$item");
                                }
                        rmdir($path);
                    };
                    $drop($path, $drop);
                }
                return !is_dir($path);
            }
            return null;
        });
    }

    /** Cria uma copia de um diretório */
    static function copy(string $path_from, string $path_to, bool $replace = false): ?bool
    {
        $path_from = path($path_from);
        $path_to = path($path_to);

        return Log::add('dir', "copy $path_from to $path_to", function () use ($path_from, $path_to, $replace) {
            if (self::check($path_from)) {
                self::create($path_to);
                $copy = function ($from, $to, $replace, $function) {
                    foreach (self::seekForDir($from) as $dir)
                        $function("$from/$dir", "$to/$dir", $replace, $function);
                    foreach (self::seekForFile($from) as $file)
                        File::copy("$from/$file", "$to/$file", $replace);
                };
                $copy($path_from, $path_to, $replace, $copy);
                return true;
            }
            return null;
        });
    }

    /** Altera o local de um diretório */
    static function move(string $path_from, string $path_to): ?bool
    {
        $path_from = path($path_from);
        $path_to = path($path_to);

        return Log::add('dir', "move $path_from to $path_to", function () use ($path_from, $path_to) {
            if (!self::check($path_to) && self::check($path_from)) {
                $path_from = path($path_from);
                $path_to = path($path_to);
                return boolval(rename($path_from, $path_to));
            }
            return null;
        });
    }

    /** Vasculha um diretório em busca de arquivos */
    static function seekForFile(string $path, bool $recursive = false): array
    {
        $path = path($path);

        return Log::add('dir', "seek for file in $path", function () use ($path, $recursive) {
            $return = [];

            foreach (self::seekForAll($path, $recursive) as $item)
                if (File::check("$path/$item"))
                    $return[] = $item;

            return $return;
        });
    }

    /** Vasculha um diretório em busca de diretórios */
    static function seekForDir(string $path, bool $recursive = false): array
    {
        $path = path($path);

        return Log::add('dir', "seek for dir in $path", function () use ($path, $recursive) {
            $return = [];

            foreach (self::seekForAll($path, $recursive) as $item)
                if (self::check("$path/$item"))
                    $return[] = $item;

            return $return;
        });
    }

    /** Vasculha um diretório em busca de arquivos e diretórios */
    static function seekForAll(string $path, bool $recursive = false): array
    {
        $path = self::getOnly($path);

        $return = [];
        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if ($item != '.' && $item != '..') {
                    $return[] = $item;
                    if ($recursive && self::check("$path/$item"))
                        foreach (self::seekForAll("$path/$item", true) as $subItem)
                            $return[] = "$item/$subItem";
                }
            }
        }

        return $return;
    }

    /** Retorna um caminho sem referenciar arquivos */
    static function getOnly(string $path): string
    {
        $path = path($path);
        if ($path != '.' && !is_dir($path)) {
            $path = explode('/', $path);

            if (strpos(end($path), '.') !== false) array_pop($path);

            $path = implode('/', $path);
        }
        return $path;
    }

    /** Verifica se um diretório existe */
    static function check(string $path): bool
    {
        return is_dir(path($path));
    }
}
