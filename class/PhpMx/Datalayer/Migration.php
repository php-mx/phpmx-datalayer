<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Scheme\Scheme;
use PhpMx\Datalayer\Scheme\SchemeField;
use PhpMx\Datalayer\Scheme\SchemeTable;

/** Classe base para definir migrations de banco com suporte a tipagem e aplicação via Scheme. */
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
            $this->fieldTime('=_created', 'moment of record creation')->default(0)->index(true),
            $this->fieldTime('=_updated', 'moment of last record update')->default(0)->index(true),
        ]);
        return $returnTable;
    }

    /** Retorna um objeto campo do tipo Boolean */
    function fieldBoolean(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'boolean', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Email */
    function fieldEmail(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'email', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Float */
    function fieldFloat(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'float', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo md5 */
    function fieldMd5(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'md5', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo MX5 */
    function fieldMx5(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'mx5', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo IDx */
    function fieldIdx(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'idx', 'comment' => $comment, 'index' => true, 'settings' => ['datalayer' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Int */
    function fieldInt(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'int', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Json */
    function fieldJson(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'json', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo String */
    function fieldString(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'string', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Text */
    function fieldText(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'text', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Time */
    function fieldTime(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'time', 'comment' => $comment]);
    }
}
