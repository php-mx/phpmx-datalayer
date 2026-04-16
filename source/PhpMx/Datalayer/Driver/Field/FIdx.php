<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;
use PhpMx\Datalayer\Driver\Record;

/** Campo de índice de referência (IDX / foreign key), com acesso direto ao registro referenciado. */
class FIdx extends Field
{
    /** @var Record */
    protected $RECORD = false;

    /**
     * Retorna o objeto Table da conexão e tabela referenciadas pelas configurações do campo.
     * @return \PhpMx\Datalayer\Driver\Table
     */
    private function _table()
    {
        $datalayer = $this->SETTINGS['datalayer'];
        $table = $this->SETTINGS['table'];
        $driverClass = 'Model\\' . strToPascalCase("db $datalayer") . '\\' . strToPascalCase("db $datalayer");
        $tableMethod = strToCamelCase($table);
        return $driverClass::${$tableMethod};
    }

    /**
     * Define o ID do registro referenciado. Aceita: ID numérico, true (usa o registro ativo), false/null (limpa), ou objeto Record.
     * @param mixed $value ID numérico, bool, null ou instância de Record.
     * @return static
     */
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

    /**
     * Retorna o objeto de registro referenciado pelo campo.
     * @return Record
     */
    function _record(): Record
    {
        if (!$this->_checkLoad())
            $this->RECORD = $this->_table()->getOne($this->get());

        return $this->RECORD;
    }

    /**
     * Salva o registro referenciado no banco de dados e atualiza o ID armazenado.
     * @return static
     */
    function _save()
    {
        $this->_record()->_save();
        $this->VALUE = $this->_record()->id;
        return $this;
    }

    /**
     * Retorna a chave de identificação numérica do registro referenciado.
     * @return int|null
     */
    function id()
    {
        return $this->get();
    }

    /**
     * Retorna a chave de identificação cifrada do registro referenciado.
     * @return string|null
     */
    function idKey(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->_table()->idToIdkey($this->get());
    }

    /**
     * Verifica se o objeto referenciado foi carregado em memória.
     * @return bool
     */
    function _checkLoad(): bool
    {
        return boolval($this->RECORD);
    }

    /**
     * Verifica se o registro referenciado pode ser salvo no banco de dados.
     * @return bool
     */
    function _checkSave(): bool
    {
        return $this->_checkLoad() ? $this->_record()->_checkSave() : !is_null($this->get());
    }

    /**
     * Verifica se o registro referenciado existe no banco de dados (id > 0).
     * @return bool
     */
    function _checkInDb(): bool
    {
        return !is_null($this->get()) && $this->get() > 0;
    }

    /**
     * Acesso mágico a propriedades: retorna id, idKey ou delega ao registro referenciado.
     * @param string $name Nome da propriedade.
     * @return mixed
     */
    function __get($name)
    {
        if ($name == 'id')
            return $this->id();

        if ($name == 'idKey')
            return $this->idKey();

        return $this->_record()->$name;
    }

    /**
     * Chamada mágica de método: delega ao registro referenciado.
     * @param string $name Nome do método.
     * @param array $arguments Argumentos da chamada.
     * @return mixed
     */
    function __call($name, $arguments)
    {
        return $this->_record()->$name(...$arguments);
    }
}
