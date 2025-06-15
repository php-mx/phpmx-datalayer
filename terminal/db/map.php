<?php

use PhpMx\Datalayer;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName = 'main')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbMap') ?? [];

        $schemePathName = strToCamelCase("db $dbName");
        $file = "storage/scheme/$schemePathName/map.json";

        Json::export($map, $file);

        self::echo("[$dbName] map exported to [$file]");
    }
};
