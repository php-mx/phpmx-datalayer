<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName = 'main', $tables = '*')
    {
        $dbName = Datalayer::formatNameToDb($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbMap') ?? [];

        $tables = $tables == '*' ? array_keys($map) : explode(',', $tables);

        $file = "storage/scheme/db/$dbName.json";

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

        Json::export($export, $file);

        self::echoLine();
        self::echo("Export ended");
    }
};
