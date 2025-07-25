<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName, $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbmap') ?? [];

        $tables = $tables == '*' ? array_keys($map) : explode(',', $tables);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        self::echo("Starting export from [$dbName] to [$file]");
        self::echoLine();

        $export = [];
        foreach ($tables as $table) {
            self::echo("| Prepare export table [$table]");
            if (isset($map[$table])) {
                $export[$table] = Query::select($table)->dbName($dbName)->run();
            } else {
                throw new Error("table [$table] not found in [$dbName]");
            }
        }

        self::echo("| Apply export");

        Json::export($file, $export);

        self::echoLine();
        self::echo("Export ended");
    }
};
