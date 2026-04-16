<?php

namespace PhpMx\Datalayer\Driver;

use Exception;

/** @ignore */
abstract class Field
{
    protected string $NAME = '';
    protected mixed $VALUE = null;
    protected array $SETTINGS = [];
    protected mixed $DEFAULT = null;
    protected bool $NULLABLE = false;

    /** @ignore */
    final function __construct(string $name, bool $nullable, mixed $default, array $settings)
    {
        $this->NAME = $name;
        $this->NULLABLE = $nullable;
        $this->DEFAULT = $default;
        $this->SETTINGS = $settings;
        $this->set($this->DEFAULT);
    }

    /**
     * Define um novo valor para o campo.
     * @param mixed $value Valor a definir (null é substituído pelo padrão se o campo não aceitar nulos).
     * @return static
     */
    function set($value): static
    {
        if (!$this->NULLABLE && is_null($value))
            $value = $this->DEFAULT;

        $this->VALUE = $value;

        return $this;
    }

    /**
     * Retorna o valor do campo para ser usado no sistema.
     * @return mixed
     */
    function get()
    {
        return $this->VALUE;
    }

    /**
     * Retorna o valor do campo formatado para persistência no banco de dados.
     * @param bool $validate Se verdadeiro valida o valor antes de retornar.
     * @return mixed
     */
    function __internalValue(bool $validate = false)
    {
        $value = $this->VALUE;

        if ($validate) $this->validade($value);

        return $value;
    }

    /**
     * Valida se o valor pode ser inserido no banco de dados.
     * Lança Exception se o campo não aceitar nulos e o valor for null.
     * @param mixed $value Valor a validar.
     * @throws \Exception
     */
    protected function validade(mixed $value): void
    {
        if (!$this->NULLABLE && is_null($value))
            throw new Exception("Not allowed null value to [$this->NAME]");
    }
}
