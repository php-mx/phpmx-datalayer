<?php

use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke(string $dbName, string $migrationName)
    {
        $migrationDbName = strToCamelCase($dbName);

        usleep(1);
        $time = microtime(true);
        $time = str_replace('.', '', $time);
        $time = str_pad($time, 14, '0');
        $time = $time . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        $migrationName = $migrationName ? strToSnakeCase("$time $migrationName") : $time;

        $file = path('system/datalayer', $migrationDbName, 'migration', $migrationName);
        $file = File::setEx($file, 'php');

        $template = Path::seekForFile('library/template/terminal/migration.txt');
        $template = Import::content($template);
        $template = prepare($template, [
            'time' => $time,
            'name' => $migrationName,
        ]);

        File::create($file, $template);

        self::echo('Migration [[#]] created successfully', $migrationName);
        self::echo("[$file]");
    }
};
