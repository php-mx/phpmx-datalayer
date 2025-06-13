<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Driver\Field;
use PhpMx\Datalayer\Driver\Record;

/** Armazena um ID de referencia para uma tabela */
class FIdx extends Field
{
    protected $DATALAYER;
    protected $TABLE;

    /** @var Record */
    protected $RECORD = false;

    protected function _formatToUse($value)
    {
        if (is_numeric($value)) {
            $value = intval($value);
            if ($value < 0)
                $value = null;
        } else if (is_bool($value)) {
            if ($value) {
                $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
                $tableMethod = strToCamelCase($this->TABLE);
                $value = $drvierClass::${$tableMethod}->active()->id();
            } else {
                $value = null;
            }
        } else {
            $driverNamespace = 'Model\\' . strToPascalCase("db $this->DATALAYER");
            $driverRecordClass = "$driverNamespace\Driver\\" . strToPascalCase("driver record $this->TABLE");
            if (is_extend($value, $driverRecordClass)) {
                $value = $value->id();
            } else {
                $value = null;
            }
        }

        return $value;
    }

    protected function _formatToInsert($value)
    {
        return $this->_formatToUse($value);
    }

    /** Define um valor do campo */
    function set($value): static
    {
        $this->VALUE = $this->_useValue($value);
        $this->RECORD = false;
        return $this;
    }

    /** Define e o nome do banco a qual a referencia pertence */
    function _datalayer($datalayer)
    {
        $this->DATALAYER = Datalayer::internalName($datalayer);
        return $this;
    }

    /** Define a tabela a qual a referencia pertence */
    function _table($table)
    {
        $this->TABLE = Datalayer::internalName($table);
        return $this;
    }

    /** Retorna o registro referenciado pelo objeto */
    function _record(): Record
    {
        if (!$this->_checkLoad()) {
            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $this->RECORD = $drvierClass::${$tableMethod}->getOne($this->get());
        }

        return $this->RECORD;
    }

    /** Salva o registro no banco de dados */
    function _save()
    {
        $this->_record()->_save();
        $this->VALUE = $this->_record()->id;
        return $this;
    }

    /** Retorna a chave de identificação numerica do registro */
    function id()
    {
        return $this->get();
    }

    /** Retorna a chave de identificação cifrada */
    function idKey(): string
    {
        $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
        $tableMethod = strToCamelCase($this->TABLE);
        return $drvierClass::${$tableMethod}->idToIdkey($this->get());
    }

    /** Verifica se o objeto referenciado pelo IDX foi carregado */
    function _checkLoad()
    {
        return boolval($this->RECORD);
    }

    /** Verifica se o registro pode ser salvo no banco de dados */
    function _checkSave()
    {
        return $this->_checkLoad() ? $this->_record()->_checkSave() : !is_null($this->get());
    }

    /** Verifica se o registro existe no banco de dados */
    function _checkInDb()
    {
        return $this->_checkSave() ? $this->_record()->_checkInDb() : false;
    }

    function __get($name)
    {
        if ($name == 'id')
            return $this->id();

        if ($name == 'idKey')
            return $this->idKey();

        return $this->_record()->$name;
    }

    function __call($name, $arguments)
    {
        return $this->_record()->$name(...$arguments);
    }
}
