<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

return new class {

    function __invoke($dbName = 'main', $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        $import = Json::import($file);

        $tables = $tables == '*' ? array_keys($import) : explode(',', $tables);

        $map = Datalayer::get($dbName)->getConfig('__dbmap') ?? [];

        Terminal::echo("Starting import from [#whiteD:$file] to [#greenB:$dbName]");

        $querys = [];
        foreach ($tables as $table) {
            if (isset($map[$table])) {
                Terminal::echo("| Prepare import table [$table]");
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

        Terminal::echo("| Apply import");

        Datalayer::get($dbName)->executeQueryList($querys);

        Terminal::echo("Import ended");
    }
};
