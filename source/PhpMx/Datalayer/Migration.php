<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Scheme\Scheme;
use PhpMx\Datalayer\Scheme\SchemeField;
use PhpMx\Datalayer\Scheme\SchemeTable;

abstract class Migration
{
    protected Scheme $scheme;
    protected string $dbName;
    protected $lock = false;

    final function execute(string $dbName, bool $mode)
    {
        $this->dbName = Datalayer::internalName($dbName);

        $this->scheme = new Scheme($this->dbName);

        $mode ? $this->up() : $this->down();

        $this->scheme->apply();
    }

    abstract function up();

    abstract function down();

    /** Retorna o objeto de uma tabela */
    function &table(string $table, ?string $comment = null): SchemeTable
    {
        $returnTable = $this->scheme->table($table, $comment)->fields([
            $this->f_time('=_created', 'smart control to create')->default(0)->index(true),
            $this->f_time('=_updated', 'smart control to update')->default(0)->index(true),
        ]);
        return $returnTable;
    }

    /** Retorna um objeto campo do tipo Boolean */
    function f_boolean(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'boolean', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Code */
    function f_code(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'code', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Config */
    function f_config(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'config', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Email */
    function f_email(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'email', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Float */
    function f_float(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'float', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Md5 */
    function f_hash(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'hash', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo IDs */
    function f_ids(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'ids', 'comment' => $comment, 'config' => ['dbName' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Idx */
    function f_idx(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'idx', 'comment' => $comment, 'config' => ['dbName' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Int */
    function f_int(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'int', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Json */
    function f_json(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'json', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Log */
    function f_log(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'log', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo String */
    function f_string(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'string', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Text */
    function f_text(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'text', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Time */
    function f_time(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'time', 'comment' => $comment]);
    }
}
