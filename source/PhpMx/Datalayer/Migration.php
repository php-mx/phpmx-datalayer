<?php

namespace PhpMx\Datalayer;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Scheme\Scheme;
use PhpMx\Datalayer\Scheme\SchemeField;
use PhpMx\Datalayer\Scheme\SchemeTable;

/** @ignore */
abstract class Migration
{
    protected Scheme $scheme;
    protected string $dbName;
    protected $lock = false;

    /** @ignore */
    final function execute(string $dbName, bool $mode)
    {
        $this->dbName = Datalayer::internalName($dbName);

        $this->scheme = new Scheme($this->dbName);

        $mode ? $this->up() : $this->down();

        $this->scheme->apply();
    }

    /**
     * Aplica as alterações do esquema (criar/alterar tabelas e campos).
     * Implementar na subclasse com as definições da migração.
     */
    abstract function up();

    /**
     * Reverte as alterações do esquema aplicadas em up().
     * Implementar na subclasse para desfazer as mudanças.
     */
    abstract function down();

    /**
     * Retorna o objeto de uma tabela para manipulação no Scheme.
     * Adiciona automaticamente os campos de controle _created, _updated e _deleted.
     * @param string $table Nome da tabela.
     * @param string|null $comment Comentário descritivo da tabela (opcional).
     * @return SchemeTable
     */
    function &table(string $table, ?string $comment = null): SchemeTable
    {
        $returnTable = $this->scheme->table($table, $comment)->fields([
            $this->fTimestamp('=_created', 'moment of record creation')->default(true)->null(false)->index(true),
            $this->fTimestamp('=_updated', 'moment of last record update')->default(null)->index(true),
            $this->fTimestamp('=_deleted', 'moment of record deletion')->default(null)->index(true),
        ]);
        return $returnTable;
    }

    /**
     * Campo TINYINT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fTinyint(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'tinyint', 'comment' => $comment]);
    }

    /**
     * Campo SMALLINT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fSmallint(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'smallint', 'comment' => $comment]);
    }

    /**
     * Campo MEDIUMINT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fMediumint(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'mediumint', 'comment' => $comment]);
    }

    /**
     * Campo INT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fInt(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'int', 'comment' => $comment]);
    }

    /**
     * Campo BIGINT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fBigint(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'bigint', 'comment' => $comment]);
    }

    /**
     * Campo DECIMAL.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fDecimal(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'decimal', 'comment' => $comment]);
    }

    /**
     * Campo FLOAT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fFloat(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'float', 'comment' => $comment]);
    }

    /**
     * Campo DOUBLE.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fDouble(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'double', 'comment' => $comment]);
    }

    /**
     * Campo BOOLEAN.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fBoolean(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'boolean', 'comment' => $comment]);
    }

    /**
     * Campo CHAR.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fChar(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'char', 'comment' => $comment]);
    }

    /**
     * Campo VARCHAR.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fVarchar(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'varchar', 'comment' => $comment]);
    }

    /**
     * Campo TEXT.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fText(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'text', 'comment' => $comment]);
    }

    /**
     * Campo DATE.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fDate(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'date', 'comment' => $comment]);
    }

    /**
     * Campo TIME.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fTime(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'time', 'comment' => $comment]);
    }

    /**
     * Campo DATETIME.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fDatetime(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'datetime', 'comment' => $comment]);
    }

    /**
     * Campo TIMESTAMP.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fTimestamp(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'timestamp', 'comment' => $comment]);
    }

    /**
     * Campo JSON.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fJson(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'json', 'comment' => $comment]);
    }

    /**
     * Campo BLOB.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fBlob(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'blob', 'comment' => $comment]);
    }

    /**
     * Campo índice de referência (foreign key).
     * @param string $name Nome do campo e da tabela referenciada.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fIdx(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, [
            'type'    => 'idx',
            'comment' => $comment,
            'index'   => true,
            'settings' => [
                'datalayer' => $this->dbName,
                'table'     => $name,
            ],
        ]);
    }

    /**
     * Campo Email.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fEmail(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'email', 'comment' => $comment]);
    }

    /**
     * Campo hash MD5.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fMd5(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'md5', 'comment' => $comment]);
    }

    /**
     * Campo Password.
     * @param string $name Nome do campo.
     * @param string|null $comment Comentário descritivo do campo (opcional).
     * @return SchemeField
     */
    function fPassword(string $name, ?string $comment = null): SchemeField
    {
        return new SchemeField($name, ['type' => 'password', 'comment' => $comment]);
    }
}
