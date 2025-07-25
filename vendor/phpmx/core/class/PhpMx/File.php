<?php

namespace PhpMx;

abstract class File
{
    /** Cria um arquivo de texto */
    static function create(string $path, string $content, bool $recreate = false): ?bool
    {
        $path = path($path);

        return Log::add('file', "create $path", function () use ($path, $content, $recreate) {
            if ($recreate || !self::check($path)) {
                $path = path($path);
                if (File::getOnly($path) != Dir::getOnly($path))
                    Dir::create($path);
                $fp = fopen($path, 'w');
                fwrite($fp, $content);
                fclose($fp);
                return true;
            }
            return null;
        });
    }

    /** Remove um arquivo */
    static function remove(string $path): ?bool
    {
        $path = path($path);

        return Log::add('file', "remove $path", function () use ($path) {
            if (self::check($path)) {
                $path = path($path);
                unlink($path);
                return !is_file($path);
            }
            return null;
        });
    }

    /** Cria uma copia de um arquivo */
    static function copy(string $path_from, string $path_to, bool $replace = false): ?bool
    {
        $path_from = path($path_from);
        $path_to = path($path_to);

        return Log::add('file', "copy $path_from to $path_to", function () use ($path_from, $path_to, $replace) {
            if ($replace || !self::check($path_to)) {
                if (self::check($path_from)) {
                    Dir::create($path_to);
                    return boolval(copy(path($path_from), path($path_to)));
                }
            }
            return null;
        });
    }

    /** Altera o local de um arquivo */
    static function move(string $path_from, string $path_to, bool $replace = false): ?bool
    {

        $path_from = path($path_from);
        $path_to = path($path_to);

        return Log::add('file', "move $path_from to $path_to", function () use ($path_from, $path_to, $replace) {
            if ($replace || !self::check($path_to)) {
                if (self::check($path_from)) {
                    Dir::create($path_to);
                    return boolval(rename(path($path_from), path($path_to)));
                }
            }
            return null;
        });
    }

    /** Retorna apenas o nome do arquivo com a extensão */
    static function getOnly(string $path): string
    {
        $path = path($path);

        $path = explode('/', $path);

        return array_pop($path);
    }

    /** Retorna apenas o nome do arquivo */
    static function getName(string $path): string
    {
        $fileName = self::getOnly($path);

        $ex = self::getEx($path);

        $ex = substr($fileName, 0, (strlen($ex) + 1) * -1);

        return $ex;
    }

    /** Retorna apenas a extensão do arquivo */
    static function getEx(string $path): string
    {
        $parts = explode('.', self::getOnly($path));

        return strtolower(end($parts));
    }

    /** Define/Altera a extensão de um arquivo */
    static function setEx(string $path, string $extension = 'php'): string
    {
        $extension = trim($extension, '.');

        if (!str_ends_with($path, ".$extension")) {
            $path = explode('.', $path);
            if (count($path) > 1) array_pop($path);
            $path[] = $extension;
            $path = implode('.', $path);
        }

        return $path;
    }

    /** Verifica se um arquivo existe */
    static function check(string $path): bool
    {
        return is_file(path($path));
    }

    /** Retorna o tamanho do arquivo */
    public static function getSize($path, $human = true): int|string
    {
        $path = path($path);

        if (!self::check($path)) return '-';

        $size = filesize($path);

        if ($human) {
            $units = [' b', ' kb', ' mb', ' gb', ' tb'];
            $i = 0;
            while ($size >= 1024 && $i < count($units) - 1) {
                $size /= 1024;
                $i++;
            }
            $size = round($size, 2) . $units[$i];
        }

        return $size;
    }

    /** Retorna a data de modificação do arquivo */
    public static function getLastModified($path): ?int
    {
        $path = path($path);

        $lastModified = self::check($path) ? filemtime($path) : null;

        return $lastModified;
    }
}
