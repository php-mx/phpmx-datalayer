<?php

namespace PhpMx\Datalayer\Driver\Field;

use PhpMx\Datalayer\Driver\Field;

/** armazena configurações e seus valores em forma de JSON */
class FConfig extends Field
{
    protected $DEFAULT = [];

    protected function _formatToUse($value)
    {
        if (is_json($value))
            $value = json_decode($value, true);

        if (!is_array($value))
            $value = [];

        return $value;
    }

    protected function _formatToInsert($value)
    {
        $value = $this->_formatToUse($value);

        $value = json_encode($value);

        return  $value;
    }

    /** Define um valor do campo ou o valor de uma configação do campo */
    function set($value): static
    {
        if (func_num_args() == 2)
            return $this->setConfig(...func_get_args());

        return parent::set($value);
    }

    /** Retorna o valor do campo ou o valor de uma configação do campo */
    function get()
    {
        if (func_num_args() == 1)
            return $this->getConfig(...func_get_args());

        return parent::get();
    }

    /** Manipula configurações do campo */
    function config($var, $value = null)
    {
        if (func_num_args() == 1) {
            return $this->getConfig($var);
        } else {
            return $this->setConfig($var, $value);
        }
    }

    /** Define uma configuração no campo */
    function setConfig($var, $value): static
    {
        $config = $this->get() ?? [];
        $config[$var] = $value;
        $this->set($config);

        return $this;
    }

    /** Retorna uma configuração do campo */
    function getConfig($var)
    {
        $config = $this->get() ?? [];
        return $config[$var] ?? null;
    }

    /** Remove uma configuração do campo */
    function removeConfig($var): static
    {
        return $this->setConfig($var, null);
    }

    /** Verifica se uma configuração existe no campo */
    function checkConfig($var): bool
    {
        $config = $this->get() ?? [];
        return isset($config[$var]);
    }
}
