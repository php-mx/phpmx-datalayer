<?php

namespace PhpMx\Datalayer\Connection;

use PDO;
use PhpMx\Datalayer;
use PhpMx\Log;

/** @ignore */
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
