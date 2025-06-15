<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;

class SchemeField
{
    protected $name;
    protected $map;

    protected $isDroped = false;

    function __construct(string $name, array $map = [], ?array $realMap = null)
    {
        $name = str_starts_with($name, '=') ? substr($name, 1) : Datalayer::internalName($name);

        $realMap = $realMap ?? SchemeMap::FIELD_MAP;

        $map['type'] = $map['type'] ?? $realMap['type'];
        $map['size'] = $map['size'] ?? $realMap['size'];
        $map['null'] = $map['null'] ?? $realMap['null'];
        $map['index'] = $map['index'] ?? $realMap['index'];
        $map['comment'] = $map['comment'] ?? $realMap['comment'];
        $map['default'] = $map['default'] ?? $realMap['default'];
        $map['settings'] = $map['settings'] ?? $realMap['settings'];

        $this->name = $name;
        $this->map = $map;
    }

    /** Marca/Desmarca o campo para a remoção */
    function drop(bool $drop = true): static
    {
        $this->isDroped = boolval($drop);
        return $this;
    }

    #==| Alterações |==#

    /** Define o comentário do campo */
    function comment(string $comment): static
    {
        $this->map['comment'] = $comment;
        return $this;
    }

    /** Define o valor padrão do campo */
    function default(mixed $default): static
    {
        $this->map['default'] = $default;
        $this->map['null'] = true;
        return $this;
    }

    /** Define o tamanho maximo */
    function size(int $size): static
    {
        $this->map['size'] = max(0, intval($size));
        return $this;
    }

    /** Define se o campo aceita valores nulos */
    function null(bool $null): static
    {
        $this->map['null'] = boolval($null);
        return $this;
    }

    /** Define se o campo deve ser indexado */
    function index(?bool $index): static
    {
        $this->map['index'] = $index;
        return $this;
    }

    #==| Recuperar de valores |==#

    /** Retorna o nome do campo */
    function getName(): string
    {
        return $this->name;
    }

    /** Retorna o mapa do campo */
    function getFildMap(): bool|array
    {
        if ($this->isDroped)
            return false;

        $map = $this->map;

        switch ($map['type']) {
            case 'id':
                $map['size'] = 10;
                $map['index'] = $map['index'] ?? true;
                break;

            case 'idx':
                $map['size'] = 10;
                $map['index'] = $map['index'] ?? true;
                $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
                $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);
                break;

            case 'int':
            case 'float':
                $map['size'] = $map['size'] ?? 10;
                break;

            case 'boolean':
                $map['size'] = 1;
                if (is_bool($map['default']))
                    $map['default'] = intval($map['default']);
                break;

            case 'email':
                $map['size'] = $map['size'] ?? 200;
                break;

            case 'hash':
                $map['size'] = 32;
                break;

            case 'code':
                $map['size'] = 34;
                break;

            case 'text':
                break;

            case 'json':
                $map['default'] = $map['default'] ?? '[]';
                break;

            case 'ids':
                $map['size'] = null;
                $map['null'] = false;
                $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
                $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);
                break;

            case 'log':
                $map['size'] = null;
                $map['null'] = false;
                break;
            case 'config':
                $map['size'] = null;
                $map['null'] = false;
                if (isset($map['default'])) {
                    if (is_array($map['default']))
                        $map['default'] = json_encode($map['default']);
                    else
                        unset($map['default']);
                }
                break;

            case 'time':
                $map['size'] = 11;
                break;

            case 'string':
            default:
                $map['type'] = 'string';
                $map['size'] = $map['size'] ?? 50;
                break;
        }
        return $map;
    }
}
