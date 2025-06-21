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
            $this->fTime('=_created', 'moment of record creation')->default(0)->index(true),
            $this->fTime('=_updated', 'moment of last record update')->default(0)->index(true),
        ]);
        return $returnTable;
    }

    /** Retorna um objeto campo do tipo Boolean */
    function fBoolean(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'boolean', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Code */
    function fCode(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'code', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Config */
    function fConfig(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'config', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Email */
    function fEmail(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'email', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Float */
    function fFloat(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'float', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Md5 */
    function fHash(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'hash', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo IDs */
    function fIds(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'ids', 'comment' => $comment, 'settings' => ['datalayer' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Idx */
    function fIdx(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'idx', 'comment' => $comment, 'index' => true, 'settings' => ['datalayer' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Int */
    function fInt(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'int', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Json */
    function fJson(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'json', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Log */
    function fLog(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'log', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo String */
    function fString(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'string', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Text */
    function fText(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'text', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Time */
    function fTime(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'time', 'comment' => $comment]);
    }
}
