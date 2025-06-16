<?php

namespace PhpMx\Datalayer\Scheme;

use Exception;
use PhpMx\Code;
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

        $this->name = $name;

        $this->map['type'] = $map['type'] ?? $realMap['type'];
        $this->map['size'] = $map['size'] ?? $realMap['size'];
        $this->map['null'] = $map['null'] ?? $realMap['null'];
        $this->map['index'] = $map['index'] ?? $realMap['index'];
        $this->map['comment'] = $map['comment'] ?? $realMap['comment'];
        $this->map['default'] = $map['default'] ?? $realMap['default'];
        $this->map['settings'] = $map['settings'] ?? $realMap['settings'];
    }

    /** Marca/Desmarca o campo para a remoção */
    function drop(bool $drop = true): static
    {
        $this->isDroped = boolval($drop);
        return $this;
    }

    /** Define o comentário do campo */
    function comment(string $comment): static
    {
        $this->map['comment'] = $comment;
        return $this;
    }

    /** Define o valor padrão do campo (f_boolean, f_code, f_config, f_email, f_float, f_hash, f_ids, f_idx, f_int, f_json, f_string, f_text, f_time) */
    function default(mixed $default): static
    {
        $this->map['default'] = $default;
        if (is_null($default)) $this->null(true);
        return $this;
    }

    /** Define o tamanho maximo ( f_float, f_int, f_string) */
    function size(int $size): static
    {
        $this->map['size'] = max(0, intval($size));
        return $this;
    }

    /** Define se o campo aceita valores nulos (f_boolean, f_code, f_email, f_float, f_hash, f_idx, f_int, f_string, f_time) */
    function null(bool $null): static
    {
        $this->map['null'] = boolval($null);
        return $this;
    }

    /** Define se o campo deve ser indexado (f_boolean, f_code, f_email, f_float, f_hash, f_idx, f_int, f_string, f_time) */
    function index(?bool $index): static
    {
        $this->map['index'] = $index;
        return $this;
    }

    /** Determina o valor máximo do campo (f_int, f_float) */
    function max(int $max): static
    {
        return $this->settings('min', num_positive($max));
    }

    /** Determina o valor minimo do campo (f_int, f_float) */
    function min(int $min): static
    {
        return $this->settings('min', num_positive($min));
    }

    /** Determina a forma de arredondamento do campo [-1:baixo,0:automático,1:cima] (f_int, f_float) */
    function round(int $round): static
    {
        return $this->settings('round', num_interval($round, -1, 1));
    }

    /** Determina quantas casas decimais o campo deve ter (f_float) */
    function decimal(int $decimal): static
    {
        return $this->settings('decimal', num_positive($decimal));
    }

    /** Determina a conexão referenciada pelo campo (f_idx, f_ids) */
    function datalayer(string $datalayer): static
    {
        return $this->settings('datalayer', Datalayer::internalName($datalayer));
    }

    /** Determina a tabela referenciada pelo campo (f_idx, f_ids) */
    function table(string $table): static
    {
        return $this->settings('table', Datalayer::internalName($table));
    }

    /** Determina se o campo deve cortar conteúdo com mais caracteres que o permitido (f_string) */
    function crop(bool $crop): static
    {
        return $this->settings('crop', $crop);
    }

    /** Armazena uma configuração dentro do campo */
    function settings(string $name, $value): static
    {
        $this->map['settings'][$name] = $value;
        return $this;
    }

    /** Retorna o nome do campo */
    function getName(): string
    {
        return $this->name;
    }

    /** Retorna o mapa do campo */
    function getMap(): bool|array
    {
        if ($this->isDroped)
            return false;

        return match ($this->map['type']) {
            'boolean' => $this->__mapBoolean($this->map),
            'code' => $this->__mapCode($this->map),
            'config' => $this->__mapConfig($this->map),
            'email' => $this->__mapEmail($this->map),
            'float' => $this->__mapFloat($this->map),
            'hash' => $this->__mapHash($this->map),
            'ids' => $this->__mapIds($this->map),
            'idx' => $this->__mapIdx($this->map),
            'int' => $this->__mapInt($this->map),
            'json' => $this->__mapJson($this->map),
            'log' => $this->__mapLog($this->map),
            'string' => $this->__mapString($this->map),
            'text' => $this->__mapText($this->map),
            'time' => $this->__mapTime($this->map),
            default => throw new Exception("Invalid field type [{$this->map['type']}] in [{$this->name}]")
        };
    }

    protected function __mapBoolean(array $map): array
    {
        $map['size'] = 1;

        if (!$map['null'] && is_null($map['default']))
            $map['default'] = false;

        if (is_bool($map['default']))
            $map['default'] = intval($map['default']);

        return $map;
    }

    protected function __mapCode(array $map): array
    {
        $map['size'] = 34;

        if (!is_null($map['default']) && !Code::check($map['default']))
            $map['default'] = Code::on($map['default']);

        return $map;
    }

    protected function __mapConfig(array $map): array
    {
        $map['size'] = null;
        $map['null'] = false;

        if (isset($map['default'])) {
            $default = $map['default'];

            if (is_json($default))
                $default = json_decode($default, true);

            if (!is_array($default))
                throw new Exception("Invalid field default value in [$this->name]");

            foreach ($default as $name => $value) {
                if (is_array($value))
                    throw new Exception("Invalid config inner value in [$this->name].[$name]");
                $map['default'][$name] = $value;
            }
        }

        $map['default'] = $map['default'] ?? [];
        $map['default'] = json_encode($map['default']);

        return $map;
    }

    protected function __mapEmail(array $map): array
    {
        $map['size'] = 254;

        if (isset($map['default']) && !is_null($map['default']) && !filter_var($map['default'], FILTER_VALIDATE_EMAIL))
            throw new Exception("Invalid field default value in [$this->name]");

        return $map;
    }

    protected function __mapFloat(array $map): array
    {
        $map['size'] = $map['size'] ?? 10;

        if (!$map['null'] && is_null($map['default']))
            $map['default'] = 0;

        if (!isset($map['settings']['decimal']))
            $map['settings']['decimal'] = 2;

        return $map;
    }

    protected function __mapHash(array $map): array
    {
        $map['size'] = 32;

        if (!is_null($map['default']) && !is_md5($map['default']))
            $map['default'] = md5($map['default']);

        return $map;
    }

    protected function __mapIds(array $map): array
    {
        $map['size'] = null;
        $map['null'] = false;
        $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
        $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);

        if (isset($map['default'])) {
            if (is_stringable($map['default']))
                $map['default'] = explode(',', $map['default']);
            $map['default'] = array_filter($map['default'], fn($v) => is_int($v));
        }

        $map['default'] = $map['default'] ?? [];
        $map['default'] = implode(',', $map['default']);

        return $map;
    }

    protected function __mapIdx(array $map): array
    {
        $map['size'] = 10;
        $map['index'] = $map['index'] ?? true;
        $map['settings']['datalayer'] = Datalayer::internalName($map['settings']['datalayer']);
        $map['settings']['table'] = Datalayer::internalName($map['settings']['table']);

        return $map;
    }

    protected function __mapInt(array $map): array
    {
        $map['size'] = $map['size'] ?? 10;

        if (!$map['null'] && is_null($map['default']))
            $map['default'] = 0;

        return $map;
    }

    protected function __mapJson(array $map): array
    {
        $map['size'] = null;
        $map['null'] = false;

        $map['default'] = $map['default'] ?? [];
        $map['default'] = is_json($map['default']) ? $map['default'] : json_encode($map['default']);

        return $map;
    }

    protected function __mapLog(array $map): array
    {
        $map['size'] = null;
        $map['null'] = false;

        $map['default'] =  [];
        $map['default'] = json_encode($map['default']);

        return $map;
    }

    protected function __mapString(array $map): array
    {
        $map['size'] = $map['size'] ?? 50;

        if (!$map['null'] && is_null($map['default']))
            $map['default'] = '';

        $map['settings']['crop'] = boolval($map['settings']['crop'] ?? false);

        return $map;
    }

    protected function __mapText(array $map): array
    {
        $map['size'] = null;
        $map['null'] = false;

        $map['default'] = $map['default'] ?? '';

        return $map;
    }

    protected function __mapTime(array $map): array
    {
        $map['size'] = 11;

        if ($map['null'] && is_null($map['default']))
            $map['default'] = 0;

        return $map;
    }
}
