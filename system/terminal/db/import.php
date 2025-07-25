<?php

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Json;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke($dbName, $tables = '*')
    {
        $dbName = Datalayer::internalName($dbName);

        $file = path("system/datalayer/$dbName/scheme/data.json");

        $import = Json::import($file);

        $tables = $tables == '*' ? array_keys($import) : explode(',', $tables);

        $map = Datalayer::get($dbName)->getConfig('__dbmap') ?? [];

        self::echo("Starting import from [$file] to [$dbName]");
        self::echoLine();

        $querys = [];
        foreach ($tables as $table) {
            if (isset($map[$table])) {
                self::echo("| Prepare import table [$table]");
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

        self::echo("| Apply import");

        Datalayer::get($dbName)->executeQueryList($querys);

        self::echoLine();
        self::echo("Import ended");
    }
};
