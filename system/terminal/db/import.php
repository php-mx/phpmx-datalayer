<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

/** Importa dados de um arquivo JSON para o banco de dados, realizando a sincronização de registros via Insert ou Update. */
return new class {

    function __invoke($dbName = 'main', $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        $import = Json::import($file);

        $tables = $tables == '*' ? array_keys($import) : explode(',', $tables);

        $map = Datalayer::get($dbName)->getConfigGroup('dbmap');

        Terminal::echol("Starting import from [#c:p,#] to [#c:p,#]", [$file, $dbName]);

        $querys = [];
        foreach ($tables as $table) {
            if (isset($map[$table])) {
                Terminal::echol("| Prepare import table [$table]");
                if (isset($import[$table])) {
                    if (!empty($import[$table])) {
                        $update = [];
                        $insert = [];
                        foreach ($import[$table] as $field) {
                            $check = count(Query::select($table)->where('id', $field['id'])->limit(1)->run($dbName));
                            if ($check) {
                                $update[] = Query::update($table)->values($field)->where('id', $field['id']);
                            } else {
                                $insert[] = $field;
                            }
                        }
                        $querys = [...$querys, ...$update];
                        if (count($insert)) $querys = [...$querys, Query::insert($table)->dbName($dbName)->values(...$insert)];
                    }
                } else {
                    throw new Error("table [$table] not found in [$file]");
                }
            }
        }

        Terminal::echol("| Apply import");

        Datalayer::get($dbName)->executeQueryList($querys);

        Terminal::echol("Database [#c:p,#] imported to [#c:p,#]", [$file, $dbName]);
    }
};
