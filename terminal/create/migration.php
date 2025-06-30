<?php

use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($migrationName)
    {
        $migrationRef = explode('.', $migrationName);

        $migrationName = array_pop($migrationRef) ?? '';
        $migrationDbName = array_pop($migrationRef) ?? 'main';

        $migrationDbName = strToCamelCase($migrationDbName);

        usleep(1);
        $time = microtime(true);
        $time = str_replace('.', '', $time);
        $time = str_pad($time, 14, '0');
        $time = $time . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        $migrationName = $migrationName ? strToSnakeCase("$time $migrationName") : $time;

        $pathRef = func_get_args();
        array_shift($pathRef);
        $pathName = path(...$pathRef);

        $migrationFile = path('migration', $migrationDbName, $pathName, $migrationName);
        $migrationFile = File::setEx($migrationFile, 'php');

        $template = Path::seekFile("storage/template/terminal/migration.txt");
        $template = Import::content($template);
        $template = prepare($template, [
            'time' => $time,
            'name' => $migrationName,
        ]);

        File::create($migrationFile, $template);

        self::echo('Migration [[#]] created successfully', $migrationName);
    }
};
