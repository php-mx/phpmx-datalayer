<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer\Query\Delete;
use PhpMx\Datalayer\Query\Insert;
use PhpMx\Datalayer\Query\Select;
use PhpMx\Datalayer\Query\Update;

/**
 * Factory para criação de queries SQL (Select, Insert, Update, Delete).
 */
abstract class Query
{

    /**
     * Retorna uma instância de query do tipo Delete.
     * @param string|array|null $table Tabela alvo da query.
     * @return Delete
     */
    static function delete(null|string|array $table = null): Delete
    {
        return new Delete($table);
    }

    /**
     * Retorna uma instância de query do tipo Insert.
     * @param string|array|null $table Tabela alvo da query.
     * @return Insert
     */
    static function insert(null|string|array $table = null): Insert
    {
        return new Insert($table);
    }

    /**
     * Retorna uma instância de query do tipo Select.
     * @param string|array|null $table Tabela alvo da query.
     * @return Select
     */
    static function select(null|string|array $table = null): Select
    {
        return new Select($table);
    }

    /**
     * Retorna uma instância de query do tipo Update.
     * @param string|array|null $table Tabela alvo da query.
     * @return Update
     */
    static function update(null|string|array $table = null): Update
    {
        return new Update($table);
    }
}
