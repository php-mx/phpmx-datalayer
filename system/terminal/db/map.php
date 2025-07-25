<?php

use PhpMx\Datalayer;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName)
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbmap') ?? [];

        $file = path("system/datalayer/$dbName/scheme/map.json");

        Json::export($file, $map);

        self::echo("[$dbName] map exported to [$file]");
    }
};
