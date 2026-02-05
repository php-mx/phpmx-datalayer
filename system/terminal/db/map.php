<?php

use PhpMx\Datalayer;
use PhpMx\Json;
use PhpMx\Terminal;

/** Exporta o mapeamento da estrutura do banco de dados para um arquivo JSON de esquema */
return new class {

    function __invoke($dbName = 'main')
    {
        $dbName = Datalayer::internalName($dbName);

        $map = Datalayer::get($dbName)->getConfigGroup('dbmap');

        $file = path("system/datalayer/$dbName/scheme/map.json");

        Json::export($file, $map);

        Terminal::echol("Map [#c:s,#] exported to [#c:p,#]", [$dbName, $file]);
    }
};
