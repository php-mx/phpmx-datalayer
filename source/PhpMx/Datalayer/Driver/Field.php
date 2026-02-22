<?php

namespace PhpMx\Datalayer\Driver;

use Exception;

/** Campo base de um registro, contendo valor, definição de nulidade e validação para persistência. */
abstract class Field
{
    protected string $NAME = '';
    protected mixed $VALUE = null;
    protected array $SETTINGS = [];
    protected mixed $DEFAULT = null;
    protected bool $NULLABLE = false;

    final function __construct(string $name, bool $nullable, mixed $default, array $settings)
    {
        $this->NAME = $name;
        $this->NULLABLE = $nullable;
        $this->DEFAULT = $default;
        $this->SETTINGS = $settings;
        $this->set($this->DEFAULT);
    }

    /** Define um novo valor para o campo */
    function set($value): static
    {
        if (!$this->NULLABLE && is_null($value))
            $value = $this->DEFAULT;

        $this->VALUE = $value;

        return $this;
    }

    /** Retorna o valor do campo para ser usado no sistema */
    function get()
    {
        return $this->VALUE;
    }

    /** Retorna o valor do campo para ser usado no banco de dados */
    function __internalValue(bool $validate = false)
    {
        $value = $this->VALUE;

        if ($validate) $this->validade($value);

        return $value;
    }

    /** Verifica se o campo pode ser insetido no banco de dados */
    protected function validade(mixed $value): void
    {
        if (!$this->NULLABLE && is_null($value))
            throw new Exception("Not allowed null value to [$this->NAME]");
    }
}
