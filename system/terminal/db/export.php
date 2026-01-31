<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

return new class {

    function __invoke($dbName = 'main', $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbmap') ?? [];

        $tables = $tables == '*' ? array_keys($map) : explode(',', $tables);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        Terminal::echo("Starting export from [#greenB:$dbName] to [#whiteD:$file]");

        $export = [];
        foreach ($tables as $table) {
            Terminal::echo("Preparing [#greenB:$table]");
            if (isset($map[$table])) {
                $export[$table] = Query::select($table)->dbName($dbName)->run();
            } else {
                throw new Error("table [$table] not found in [$dbName]");
            }
        }

        Terminal::echo("Apply export");

        Json::export($file, $export);

        Terminal::echo("Export ended");
    }
};
