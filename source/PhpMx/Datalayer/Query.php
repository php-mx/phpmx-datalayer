<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer\Query\Delete;
use PhpMx\Datalayer\Query\Insert;
use PhpMx\Datalayer\Query\Select;
use PhpMx\Datalayer\Query\Update;

abstract class Query
{
    /** Retorna uma instancia de query do tipo Delete */
    static function delete(null|string|array $table = null): Delete
    {
        return new Delete($table);
    }

    /** Retorna uma instancia de query do tipo Insert */
    static function insert(null|string|array $table = null): Insert
    {
        return new Insert($table);
    }

    /** Retorna uma instancia de query do tipo Select */
    static function select(null|string|array $table = null): Select
    {
        return new Select($table);
    }

    /** Retorna uma instancia de query do tipo Update */
    static function update(null|string|array $table = null): Update
    {
        return new Update($table);
    }
}
