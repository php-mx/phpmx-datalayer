<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** Armazena linhas de Log em forma de JSON */
class FLog extends Field
{
    protected $DEFAULT = [];

    protected function _formatToUse($value)
    {
        if (is_json($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = [];

        foreach ($value as $pos => $line)
            if (!is_array($line) || !is_numeric($line[0])) {
                unset($value[$pos]);
            }

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        $value = json_encode($value);

        return  $value;
    }

    /** Define um valor do log ou adiciona uma linha ao log */
    function set($value): static
    {
        if (is_string($value) && !is_json($value))
            return $this->add($value);

        return parent::set($value);
    }

    /** Adiciona uma linha ao log */
    function add($message): static
    {
        $currentValue = $this->get() ?? [];
        $newValue = [...$currentValue, [time(), $message]];
        $this->set($newValue);
        return $this;
    }
}
