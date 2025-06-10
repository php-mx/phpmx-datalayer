<?php

namespace PhpMx;

use PhpMx\Datalayer\Connection;
use PhpMx\Datalayer\Connection\Mysql;
use PhpMx\Datalayer\Connection\Sqlite;
use Error;

abstract class Datalayer
{
    /** @var Connection[] */
    protected static $instance = [];

    protected static $type = [
        'MYSQL' => Mysql::class,
        'SQLITE' => Sqlite::class
    ];

    /** Retorna um objeto datalayer */
    static function &get(string $dbName): Connection
    {
        $dbName = self::formatNameToDb($dbName);

        if (!isset(self::$instance[$dbName]))
            self::register($dbName);

        return self::$instance[$dbName];
    }

    /** Registra um datalayer */
    static function register(string $dbName, array $data = []): void
    {
        $dbName = self::formatNameToDb($dbName);
        log_add('datalayer', 'register [#]', [$dbName], function () use ($dbName, $data) {
            $data['type'] = $data['type'] ?? env(strtoupper("DB_" . $dbName . "_TYPE"));

            if (!$data['type'])
                throw new Error("datalayer type required to [$dbName]");

            $type = strtoupper($data['type']);

            if (!isset(self::$type[$type]))
                throw new Error("connection type [$type] not found");

            $connection = self::$type[$type];

            if (!class_exists($connection))
                throw new Error("connection class [$connection] not found");

            self::$instance[$dbName] = new $connection($dbName, $data);
        });
    }

    #==| Tools |==#

    /** Formata uma string de nome para ser usada no banco de dados */
    static function formatNameToDb(string $name): string
    {
        $name = preg_split('/(?=[A-Z])/', $name);
        $name = array_filter($name);
        $name = implode('_', $name);
        $name = str_replace_all('__', '_', $name);
        $name = trim($name);
        $name = strtolower($name);
        return $name;
    }

    /** Formata uma string de nome para ser usada como de tabela no datalayer */
    static function formatNameToClass(string $name): string
    {
        $name = self::formatNameToDb($name);
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    /** Formata uma string de nome para ser usada como referencia de um metodo ou variavel */
    static function formatNameToMethod(string $name): string
    {
        $name = self::formatNameToClass($name);
        $name = lcfirst($name);
        return $name;
    }

    /** Formata uma string de nome para ser usada como namespace do driver de um datalayer */
    static function formatNameToDriverNamespace(string $name): string
    {
        $name = self::formatNameToClass($name);
        $name = "Model\\Db$name";
        return $name;
    }

    /** Formata uma string de nome para ser usada como referencia da classe de driver de um datalayer */
    static function formatNameToDriverClass(string $name): string
    {
        $namespace = self::formatNameToDriverNamespace($name);
        $class = self::formatNameToClass($name);
        $name = "$namespace\\Db$class";
        return $name;
    }
}
