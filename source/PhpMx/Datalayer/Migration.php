<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Query\Delete;
use PhpMx\Datalayer\Query\Insert;
use PhpMx\Datalayer\Query\Update;
use PhpMx\Datalayer\Scheme\SchemeField;
use PhpMx\Datalayer\Scheme\SchemeTable;

abstract class Migration
{
    protected Scheme $scheme;
    protected string $dbName;
    protected array $runList = [];
    protected $lock = false;

    final function execute(string $dbName, bool $mode)
    {

        $this->dbName = Datalayer::formatNameToDb($dbName);

        $this->scheme = new Scheme($this->dbName);

        $mode ? $this->up() : $this->down();

        $this->scheme->apply();

        array_map(fn($run) => $run(), $this->runList);
    }

    abstract function up();

    abstract function down();

    /** Adiciona um script a lista de execução */
    protected function script(callable $function)
    {
        $this->runList[] = $function;
    }

    /** Adiciona uma query insert a lista de execução */
    protected function &queryInsert(string $table): Insert
    {
        $query = new Insert(Datalayer::formatNameToDb($table));
        $this->runList[] = fn() => Datalayer::get($this->dbName)->executeQuery($query);
        return $query;
    }

    /** Adiciona uma query update a lista de execução */
    protected function &queryUpdate(string $table): Update
    {
        $query = new Update(Datalayer::formatNameToDb($table));
        $this->runList[] = fn() => Datalayer::get($this->dbName)->executeQuery($query);
        return $query;
    }

    /** Adiciona uma query delete a lista de execução */
    protected function &queryDelete(string $table): Delete
    {
        $query = new Delete(Datalayer::formatNameToDb($table));
        $this->runList[] = fn() => Datalayer::get($this->dbName)->executeQuery($query);
        return $query;
    }

    /** Retorna o objeto de uma tabela */
    function &table(string $table, ?string $comment = null): SchemeTable
    {
        $returnTable = $this->scheme->table($table, $comment)->fields([
            $this->fTime('_created', 'smart control to create')->default(0)->index(true),
            $this->fTime('_updated', 'smart control to update')->default(0)->index(true),
        ]);
        return $returnTable;
    }

    /** Retorna um objeto campo do tipo Int */
    function fInt(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'int', 'comment' => $comment]);
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

    /** Retorna um objeto campo do tipo Json */
    function fJson(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'json', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Float */
    function fFloat(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'float', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Idx */
    function fIdx(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'idx', 'comment' => $comment, 'config' => ['dbName' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo IDs */
    function fIds(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'ids', 'comment' => $comment, 'config' => ['dbName' => $this->dbName, 'table' => $name]]);
    }

    /** Retorna um objeto campo do tipo Boolean */
    function fBoolean(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'boolean', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Email */
    function fEmail(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'email', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Md5 */
    function fHash(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'hash', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Hash Code */
    function fCode(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'code', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Log */
    function fLog(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'log', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Config */
    function fConfig(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'config', 'comment' => $comment]);
    }

    /** Retorna um objeto campo do tipo Time */
    function fTime(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'time', 'comment' => $comment]);
    }
}
