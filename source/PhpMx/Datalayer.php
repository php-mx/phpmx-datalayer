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
        $dbName = self::internalName($dbName);

        if (!isset(self::$instance[$dbName]))
            self::register($dbName);

        return self::$instance[$dbName];
    }

    /** Registra um datalayer */
    static function register(string $dbName, array $data = []): void
    {
        $dbName = self::internalName($dbName);

        log_add('datalayer.register', '[#]', [self::externalName($dbName, 'Db')], function () use ($dbName, $data) {
            $envName = strtoupper($dbName);

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

    /** Converte um nome para uso no banco de dados  */
    static function internalName(string $name): string
    {
        $name = self::externalName($name);
        $name = strToSnakeCase($name);
        return $name;
    }

    /** Converte um nome para uso no codigo  */
    static function externalName(string $name, ?string $prefix = null): string
    {
        $name = strToCamelCase("$prefix $name");
        return $name;
    }
}
