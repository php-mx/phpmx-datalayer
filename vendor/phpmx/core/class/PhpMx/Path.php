<?php

namespace PhpMx;

abstract class Path
{
    static protected array $paths = [];

    /** Formata um caminho de diretório */
    static function format(): string
    {
        $path = array_values(func_get_args());
        $path = implode('/', $path);
        $path = str_replace('\\', '/', $path);
        $path = str_replace_all('//', '/', $path);

        $currentPath = getcwd();
        $currentPath = str_replace('\\', '/', $currentPath);
        $currentPath = rtrim($currentPath, '/');

        if (str_starts_with($path, $currentPath))
            $path = substr($path, strlen($currentPath));

        $path = ltrim($path, '/');

        if (str_starts_with($path, './'))
            $path = substr($path, 2);

        $path = str_trim($path, '/', '/ ');
        $path = str_replace_all('//', '/', $path);

        return $path;
    }

    /** Registra um novo caminho para importação de arquivos */
    static function register($path): void
    {
        self::$paths[] = self::format($path);
    }

    /** Retorna os caminhos registrados em path */
    static function registred(): array
    {
        return array_reverse(self::$paths);
    }

    /** Busca e retorna um arquivo utilizando os caminhos registrados */
    static function seekForFile(): ?string
    {
        $path = self::format(...func_get_args());

        foreach (self::registred() as $registred)
            if (File::check("$registred/$path"))
                return self::format("$registred/$path");

        return null;
    }

    /** Busca e retorna todos os arquivos utilizando os caminhos registrados */
    static function seekForFiles(): array
    {
        $path = self::format(...func_get_args());

        $result = [];

        foreach (self::registred() as $registred)
            if (File::check("$registred/$path")) {
                $file = self::format("$registred/$path");
                $result[md5($file)] = $file;
            }


        return array_values($result);
    }

    /** Busca e retorna um diretório utilizando os caminhos registrados */
    static function seekForDir(): ?string
    {
        $path = self::format(...func_get_args());

        foreach (self::registred() as $registred)
            if (Dir::check("$registred/$path"))
                return self::format("$registred/$path");

        return null;
    }

    /** Busca e retorna todos os diretórios utilizando os caminhos registrados */
    static function seekForDirs(): array
    {
        $path = self::format(...func_get_args());

        $result = [];

        foreach (self::registred() as $registred)
            if (Dir::check("$registred/$path")) {
                $dir = self::format("$registred/$path");
                $result[md5($dir)] = $dir;
            }

        return array_values($result);
    }
}
