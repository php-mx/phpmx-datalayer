<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use PhpMx\Datalayer\Driver\Record;

class FIdx extends Field
{
    /** @var Record */
    protected $RECORD = false;

    private function _table()
    {
        $datalayer = $this->SETTINGS['datalayer'];
        $table = $this->SETTINGS['table'];
        $driverClass = 'Model\\' . strToPascalCase("db $datalayer") . '\\' . strToPascalCase("db $datalayer");
        $tableMethod = strToCamelCase($table);
        return $driverClass::${$tableMethod};
    }

    function set($value): static
    {
        if (is_numeric($value)) {
            $value = intval($value);
            if ($value < 0) {
                $value = null;
            }
        } else if (is_bool($value)) {
            $value = $value ? $this->_table()->active()->id() : null;
        } else {
            $datalayer = $this->SETTINGS['datalayer'];
            $table = $this->SETTINGS['table'];
            $driverNamespace = 'Model\\' . strToPascalCase("db $datalayer");
            $driverRecordClass = "$driverNamespace\Driver\\" . strToPascalCase("driver record $table");
            $value = is_extend($value, $driverRecordClass) ? $value->id() : null;
        }

        $this->RECORD = false;

        return parent::set($value);
    }

    /** Retorna o registro referenciado pelo objeto */
    function _record(): Record
    {
        if (!$this->_checkLoad())
            $this->RECORD = $this->_table()->getOne($this->get());

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
    function idKey(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->_table()->idToIdkey($this->get());
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
        return !is_null($this->get()) && $this->get() > 0;
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
