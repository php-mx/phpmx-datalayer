<?php

namespace PhpMx\Datalayer\Driver\Field;

use Exception;
use PhpMx\Datalayer\Driver\Field;

/** Campo de texto com tamanho variável (VARCHAR), com suporte a corte automático e validação de tamanho máximo. */
class FVarchar extends Field
{
    /**
     * Define o valor do campo como string, aplicando corte se configurado e removendo espaços nas extremidades.
     * @param mixed $value Valor a definir.
     * @return static
     */
    function set($value): static
    {
        if (!is_null($value)) {
            $value = strval($value);

            if ($this->SETTINGS['crop'] ?? false)
                $value = substr($value, 0, $this->SETTINGS['size']);

            $value = trim($value);
        }
        return parent::set($value);
    }

    /**
     * Valida se o valor não excede o tamanho máximo configurado para o campo.
     * @param mixed $value Valor a validar.
     * @throws \Exception
     */
    protected function validade(mixed $value): void
    {
        parent::validade($value);

        if (!is_null($value) && strlen($value) > $this->SETTINGS['size'])
            throw new Exception("Value exceeds maximum size [{$this->SETTINGS['size']}] in [$this->NAME]");
    }
}
