<?php

use PhpMx\Datalayer;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName = 'main')
    {
        $dbName = Datalayer::formatNameToDb($dbName);

        $map = Datalayer::get($dbName)->getConfig('__dbMap') ?? [];

        $file = "storage/scheme/db/$dbName.map.json";

        Json::export($map, $file);

        self::echo("Mapa do datalayer [$dbName] exportado para [$file]");
    }
};
