<?php

namespace PhpMx\Datalayer\Connection;

use PDO;
use PhpMx\Datalayer;
use PhpMx\Log;

/**
 * Driver de conexão para MariaDB via PDO.
 * Estende Mysql, sobrescrevendo apenas o log de inicialização da conexão.
 * @ignore
 */
class Mariadb extends Mysql
{
    /** Retorna a instancia PDO da conexão */
    protected function &pdo(): PDO
    {
        if (is_array($this->instancePDO)) {
            Log::add('datalayer.start', prepare('[#] mariadb', Datalayer::externalName($this->dbName, 'Db')), function () {
                $this->instancePDO = new PDO(...$this->instancePDO);
            });
        }
        return $this->instancePDO;
    }
}
