<?php

use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

/**
 * Cria um novo arquivo de migration com timestamp único e template base no banco especificado.
 * @param string $migrationName Nome descritivo da migration.
 * @param string $dbName Nome do banco de dados alvo (opcional, usa 'main' por padrão).
 */
return new class {

    function __invoke(string $migrationName, string $dbName = 'main')
    {
        $migrationDbName = strToCamelCase($dbName);

        usleep(1);
        $time = microtime(true);
        $time = str_replace('.', '', $time);
        $time = str_pad($time, 14, '0');
        $time = $time . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        $migrationName = strToSnakeCase("$time $migrationName");

        $file = path('system/datalayer', $migrationDbName, 'migration', $migrationName);
        $file = File::setEx($file, 'php');

        $template = Path::seekForFile('library/template/terminal/migration.txt');
        $template = Import::content($template);
        $template = prepare($template, [
            'time' => $time,
            'name' => $migrationName,
        ]);

        File::create($file, $template);

        Terminal::echol("File [#c:p,#] created successfully", $file);
    }
};
