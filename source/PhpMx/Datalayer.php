<?php

namespace PhpMx;

use Exception;
use PhpMx\Datalayer\Connection\BaseConnection;
use PhpMx\Datalayer\Connection\Mariadb;
use PhpMx\Datalayer\Connection\Mysql;
use PhpMx\Datalayer\Connection\Postgresql;
use PhpMx\Datalayer\Connection\Sqlite;

/**
 * Gerencia conexões reutilizáveis com múltiplos bancos de dados.
 * Suporta MySQL, MariaDB, SQLite e PostgreSQL configurados via variáveis de ambiente.
 */
abstract class Datalayer
{
    /** @var BaseConnection[] */
    protected static $instance = [];

    protected static $type = [
        'MYSQL' => Mysql::class,
        'MARIADB' => Mariadb::class,
        'SQLITE' => Sqlite::class,
        'POSTGRESQL' => Postgresql::class,
    ];

    /**
     * Retorna a conexão ativa com o banco de dados, registrando-a na primeira chamada.
     * @param string $dbName Nome do banco de dados.
     * @return BaseConnection
     */
    static function &get(string $dbName): BaseConnection
    {
        $dbName = self::internalName($dbName);

        if (!isset(self::$instance[$dbName]))
            self::register($dbName);

        return self::$instance[$dbName];
    }

    /**
     * Registra uma nova conexão com o banco de dados.
     * @param string $dbName Nome do banco de dados.
     * @param array $data Dados de configuração da conexão (opcional, usa variáveis de ambiente por padrão).
     */
    static function register(string $dbName, array $data = []): void
    {
        $dbName = self::internalName($dbName);

        Log::add('datalayer.register', self::externalName($dbName, 'Db'), function () use ($dbName, $data) {
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

    /**
     * Converte um nome para o formato interno de uso no banco de dados (snake_case).
     * @param string $name Nome a converter.
     * @return string
     */
    static function internalName(string $name): string
    {
        $name = self::externalName($name);
        $name = strToSnakeCase($name);
        return $name;
    }

    /**
     * Converte um nome para o formato externo de uso no código (camelCase).
     * @param string $name Nome a converter.
     * @param string|null $prefix Prefixo a concatenar ao nome (opcional).
     * @return string
     */
    static function externalName(string $name, ?string $prefix = null): string
    {
        $name = strToCamelCase("$prefix $name");
        return $name;
    }
}
