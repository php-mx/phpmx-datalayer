<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer;
use PhpMx\Datalayer\Driver\Field;
use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Query\Select;

/** Armazena IDs de referencia para uma tabela */
class FIds extends Field
{
    protected $DEFAULT = [];

    protected $DATALAYER;
    protected $TABLE;

    protected function _formatToUse($value)
    {
        if (is_string($value))
            $value = explode(',', $value);

        if (!is_array($value))
            $value = [];

        $value = array_map(fn($v) => intval($v), $value);
        $value = array_filter($value, fn($v) => boolval($v));
        $value = array_unique($value);
        sort($value);

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        $value = implode(',', $value);

        return  $value;
    }

    /** Remove um ou mais IDs a lista */
    function add(): static
    {
        $add = [];
        foreach (func_get_args() as $arg)
            if (is_array($arg))
                $add = [...$add, ...array_values($arg)];
            else
                $add[] = $arg;

        $add = $this->_formatToUse($add);

        $currentValue = $this->get() ?? [];

        $newValue = [...$currentValue, ...$add];

        $this->set($newValue);

        return $this;
    }

    /** Remove um ou mais IDs da lista */
    function remove(): static
    {
        $remove = [];
        foreach (func_get_args() as $arg)
            if (is_array($arg))
                $remove = [...$remove, ...array_values($arg)];
            else
                $remove[] = $arg;

        $remove = $this->_formatToUse($remove);

        $currentValue = $this->get() ?? [];

        foreach ($remove as $removeValue)
            foreach ($currentValue as $pos => $value)
                if ($value == $removeValue)
                    unset($currentValue[$pos]);

        $this->set($currentValue);

        return $this;
    }

    /** Verifica se um item está referenciado no campo */
    function check(int $id): bool
    {
        return in_array($id, $this->get() ?? []);
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

    /** Retorna um SELECT buscando todos os registros representados no objeto */
    function query(): Select
    {
        $values = $this->_insert();

        $where = empty($values) ? 'false' : "id in ($values)";

        return Query::select($this->TABLE)
            ->dbName($this->DATALAYER)
            ->where($where);
    }
}
