<?php

use PhpMx\Dir;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($forceDev = 0)
    {
        $composer = Json::import('composer');

        $composer['autoload'] = $composer['autoload'] ?? [];
        $composer['autoload']['psr-4'] = $composer['autoload']['psr-4'] ?? [];
        $composer['autoload']['files'] = $composer['autoload']['files'] ?? [];

        $composer['autoload']['psr-4'][''] = path('class/');

        $autoImport = path('system/helper/');

        $files = [];

        foreach ($composer['autoload']['files'] as $file)
            if (substr($file, 0, strlen($autoImport)) != $autoImport)
                $files[] = $file;

        $files = [...$files, ...self::seekForFile($autoImport)];

        $composer['autoload']['files'] = $files;

        Json::export('composer', $composer, false);

        self::echo('File [composer.json] updated');

        $forceDev || env('DEV') ? self::instalInDev() : self::instalInProd();
    }

    protected static function instalInDev()
    {
        self::echoLine();
        self::echo('run [composer install]');
        self::echoLine();
        echo shell_exec("composer install");
        self::echoLine();
    }

    protected static function instalInProd()
    {
        self::echoLine();
        self::echo('run [composer install --no-dev --optimize-autoloader]');
        self::echoLine();
        echo shell_exec("composer install --no-dev --optimize-autoloader");
        self::echoLine();
    }

    protected static function seekForFile($ref)
    {
        $return = [];

        foreach (Dir::seekForDir($ref) as $dir)
            foreach (self::seekForFile("$ref/$dir") as $file)
                $return[] = path($file);

        foreach (Dir::seekForFile($ref) as $file)
            $return[] = path("$ref/$file");

        return $return;
    }
};
