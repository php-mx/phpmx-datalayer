<?php

use PhpMx\Datalayer;
use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($migrationRef = '')
    {
        $migrationRef = explode('.', $migrationRef);

        $migrationName = array_pop($migrationRef) ?? '';
        $migrationDbName = array_pop($migrationRef) ?? 'main';

        $migrationDbName = Datalayer::formatNameToClass($migrationDbName);

        $time = time();

        $migrationName = $migrationName ? $time . "_" . $migrationName : $time;

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
