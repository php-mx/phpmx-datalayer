<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

/** Exporta os dados das tabelas mapeadas no dbmap para um arquivo JSON de sementes. */
return new class {

    function __invoke($dbName = 'main', $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfigGroup('dbmap');

        $tables = $tables == '*' ? array_keys($map) : explode(',', $tables);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        Terminal::echol("Starting export from [#c:p,#] to [#c:p,#]", [$dbName, $file]);

        $export = [];
        foreach ($tables as $table) {
            Terminal::echol("Preparing [#c:s,$table]");
            if (isset($map[$table])) {
                $export[$table] = Query::select($table)->dbName($dbName)->run();
            } else {
                throw new Error("table [$table] not found in [$dbName]");
            }
        }

        Terminal::echol("Apply export");

        Json::export($file, $export);

        Terminal::echol("Database [#c:p,#] exported to [#c:p,#]", [$dbName, $file]);
    }
};
