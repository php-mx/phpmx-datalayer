<?php

namespace PhpMx;

use Exception;
use PhpMx\Datalayer\Connection\BaseConnection;
use PhpMx\Datalayer\Connection\Mysql;
use PhpMx\Datalayer\Connection\Sqlite;

abstract class Datalayer
{
    /** @var BaseConnection[] */
    protected static $instance = [];

    protected static $type = [
        'MYSQL' => Mysql::class,
        'SQLITE' => Sqlite::class
    ];

    /** Retorna um objeto datalayer */
    static function &get(string $dbName): BaseConnection
    {
        $dbName = strToSnakeCase($dbName);

        if (!isset(self::$instance[$dbName]))
            self::register($dbName);

        return self::$instance[$dbName];
    }

    /** Registra um datalayer */
    static function register(string $dbName, array $data = []): void
    {
        $dbName = strToSnakeCase($dbName);

        log_add('db.register', 'Db[#]', [strToPascalCase($dbName)], function () use ($dbName, $data) {
            $envName = strToSnakeCase($dbName);
            $envName = strtoupper($envName);

            $data['type'] = $data['type'] ?? env("DB_{$envName}_TYPE");

            if (!$data['type'])
                throw new Exception("datalayer type required to [$dbName]");

            $type = strtoupper($data['type']);

            if (!isset(self::$type[$type]))
                throw new Exception("connection type [$type] not found");

            $connection = self::$type[$type];

            if (!class_exists($connection))
                throw new Exception("connection class [$connection] not found");

            self::$instance[$dbName] = new $connection($dbName, $data);
        });
    }
}
