<?php

namespace PhpMx\Datalayer\Driver;

use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Driver\Field\FIdx;
use Error;
use PhpMx\Log;

/**
 * @property int|null $id chave de identificação numerica do registro
 */
abstract class Record
{
    /** @var PhpMx\Datalayer\Driver\Field[]|FIdx[]| */
    protected array $FIELD = [];

    protected ?int $ID = null;

    protected array $INITIAL = [];

    protected string $DATALAYER;
    protected string $TABLE;

    protected bool $DELETE = false;

    function __construct(array $scheme)
    {
        $this->_arraySet($scheme);

        $this->ID = $scheme['id'] ?? null;
        $this->INITIAL = $this->__insertValues();

        if ($this->_checkInDb()) {
            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheSet($this->ID, $this);
        }
    }

    /** Retorna a chave de identificação numerica do registro */
    final function id(): ?int
    {
        return $this->ID;
    }

    /** Retorna a chave de identificação cifrada */
    final function idKey(): ?string
    {
        if (!$this->_checkInDb()) return null;
        $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
        $tableMethod = strToCamelCase($this->TABLE);
        return $drvierClass::${$tableMethod}->idToIdkey($this->id);
    }

    /** Retorna o momento em que o campo foi criado */
    final function _created(): int
    {
        return $this->FIELD['_created']->get();
    }

    /** Retorna o momento da ultima atualização do campo  */
    final function _updated(): int
    {
        return $this->FIELD['_updated']->get();
    }

    /** Retorna o momento da ultima mudança (create ou update) do campo  */
    final function _changed(): int
    {
        return $this->_updated() ? $this->_updated() : $this->_created();
    }

    /** Retorna o valor do esquema de um campo do registro */
    final function _schemeValue(string $field)
    {
        $field = str_starts_with($field, '_') ? $field : strToCamelCase($field);
        return method_exists($this, "_scheme_$field") ? $this->{"_scheme_$field"}() : $this->_array($field)[$field];
    }

    /** Retorna o esquema dos campos do registro tratados em forma de array */
    final function _scheme(array $fields): array
    {
        $scheme = [];

        foreach ($fields as $pos => $field) {

            $fieldName = is_numeric($pos) ? $field : $pos;
            $schemeWraper =  is_numeric($pos) ? fn($record) => $record->_schemeValue($field) : $field;
            $schemeWraper = !is_callable($schemeWraper) ? fn($record) => $schemeWraper : $schemeWraper;

            $scheme[$fieldName] = $schemeWraper($this);
        }

        return $scheme;
    }

    /** Retorna todo os campos e esquemas personalizados do registro tratados em forma de array */
    final function _schemeAll(array $fieldsRemove = []): array
    {
        $fields = [
            'idKey',
            '_changed',
            ...array_keys($this->FIELD)
        ];

        $fields = array_flip($fields);

        foreach (get_class_methods(static::class) as $class) {
            if (str_starts_with($class, '_scheme_')) {
                $fieldName = substr($class, 8);
                if (!is_array($fields[$fieldName]))
                    $fields[$fieldName] = count($fields);
            }
        }

        foreach ($fieldsRemove as $remove)
            if (isset($fields[$remove]))
                unset($fields[$remove]);

        $fields = array_flip($fields);
        $fields = array_values($fields);

        return $this->_scheme($fields);
    }

    /** Retorna o esquema de _changed */
    final protected function _scheme__changed()
    {
        return $this->_changed();
    }

    /** Marca o registro como ativo */
    final function _makeActive(): static
    {
        $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
        $tableMethod = strToCamelCase($this->TABLE);

        $drvierClass::${$tableMethod}->active($this);
        return $this;
    }

    /** Retorna os campos do registro em forma de array */
    final function _array(...$fields)
    {
        if (empty($fields))
            $fields = ['id', 'idKey', ...array_keys($this->FIELD)];

        $scheme = [];

        foreach ($fields as $field) {
            if ($field == 'id') {
                $scheme[$field] = $this->id();
            } else if ($field == 'idKey') {
                $scheme[$field] = $this->idKey();
            }
            if (str_starts_with($field, '_')) {
                if (isset($this->FIELD[$field]))
                    $scheme[$field] = $this->FIELD[$field]->get();
            } else {
                $name = strToCamelCase($field);
                if (isset($this->FIELD[$name]))
                    $scheme[$field] = $this->FIELD[$name]->get();
            }
        }

        return $scheme;
    }

    /** Define os valores dos campos do registro com base em um array */
    final function _arraySet(mixed $scheme): static
    {
        if (is_array($scheme)) {
            foreach ($scheme as $name => $value) {
                $name = str_starts_with($name, '_') ? $name : strToCamelCase($name);
                if (isset($this->FIELD[$name]))
                    $this->FIELD[$name]->set($value);
            }
        }
        return $this;
    }

    /** Aplica um array de mudanças aos campos do registro */
    final function _arrayChange(array $changes): static
    {
        $array = $this->_array();
        applyChanges($array, $changes);
        $this->_arraySet($array);
        return $this;
    }

    /** Verifica se o registro existe no banco de dados */
    final function _checkInDb(): bool
    {
        return !is_null($this->id()) && $this->id() > 0;
    }

    /** Verifica se algum dos campos fornecidos foi alterado */
    final function _checkChange(...$fields): bool
    {
        $initial = $this->INITIAL;
        $current = $this->__insertValues();

        if (empty($fields))
            return $initial != $current;

        $fields = array_map(fn($v) => str_starts_with($v, '_') ? $v : strToSnakeCase($v), $fields);

        foreach ($fields as $field)
            if ($initial[$field] != $current[$field])
                return true;

        return false;
    }

    /** Verifica se o registro pode ser salvo no banco de dados */
    final function _checkSave(): bool
    {
        return !is_null($this->id()) && $this->id() >= 0;
    }

    /** Retorna o array dos campos da forma como são salvos no banco de dados */
    final protected function __insertValues(bool $validate = false): array
    {
        $return = [];

        foreach ($this->FIELD as $name => $field) {
            $name = str_starts_with($name, '_') ? $name : strToSnakeCase($name);
            $return[$name] = $field->__internalValue($validate);
        }

        return $return;
    }

    /** Prepara o registro para ser excluido PERMANENTEMENTE no proximo _save */
    final function _delete(bool $delete): static
    {
        $this->DELETE = $delete;
        return $this;
    }

    /** Salva o registro no banco de dados */
    final function _save(bool $forceUpdate = false): static
    {
        Log::add(
            'driver.save',
            prepare("[#].[#]", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]),
            function () use ($forceUpdate) {
                if ($this->_checkSave()) {
                    match (true) {
                        $this->DELETE => $this->__runDelete(),
                        $this->_checkInDb() => $this->__runUpdate($forceUpdate),
                        default => $this->__runCreate()
                    };
                } else {
                    Log::changeScope('driver.save.aborted', prepare('[#].[#] record cannot be saved', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]));
                }
            }
        );

        return $this;
    }

    /** Executa o comando parar salvar os registros referenciados via IDX */
    final protected function __runSaveIdx()
    {
        foreach ($this->FIELD as &$field) {
            if (is_class($field, FIdx::class) && $field->_checkLoad() && $field->_checkSave())
                if (!$field->id ||  $field->id != $this->ID || !is_class($field->_record(), $this::class))
                    $field->_save();
        }
    }

    /** Executa o comando parar criar o registro */
    final protected function __runCreate()
    {
        $this->__runSaveIdx();
        $onCreate = $this->_onCreate() ?? null;
        if ($onCreate ?? true) {
            $this->FIELD['_created']->set(true);

            $this->ID = Query::insert($this->TABLE)
                ->values($this->__insertValues(true))
                ->run($this->DATALAYER);

            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheSet($this->ID, $this);

            if (is_callable($onCreate))
                $onCreate($this);
        } else {
            Log::changeScope('driver.create.aborted', '[#].[#]() aborted in _onCreate', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]);
        }
    }

    /** Executa o comando parar atualizar o registro */
    final protected function __runUpdate(bool $forceUpdate)
    {
        Log::changeScope('driver.update', "[#].[#]([#])", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]);
        $this->__runSaveIdx();
        if ($forceUpdate || $this->_checkChange()) {
            $onUpdate = $this->_onUpdate() ?? null;
            if ($onUpdate ?? true) {
                $dif = $this->__insertValues(true);

                foreach ($dif as $name => $value)
                    if ($value == $this->INITIAL[$name])
                        unset($dif[$name]);

                $dif['_updated'] = time();
                $this->FIELD['_updated']->set($dif['_updated']);

                foreach ($dif as $name => $value)
                    $this->INITIAL[$name] = $value;

                Query::update($this->TABLE)
                    ->where('id', $this->ID)
                    ->values($dif)
                    ->run($this->DATALAYER);

                if (is_callable($onUpdate))
                    $onUpdate($this);
            } else {
                Log::changeScope('driver.update.aborted', '[#].[#]([#]) aborted in _onUpdate', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]);
            }
        } else {
            Log::changeScope('driver.update.ignored', "[#].[#]([#]) unchanged values",  [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]);
        }
    }

    /** Executa o comando para deletar o registro do banco de dados */
    final protected function __runDelete()
    {
        Log::changeScope('driver.delete', "[#].[#]([#])", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]);
        $onDelete = $this->_onDelete() ?? null;
        if ($onDelete ?? true) {
            Query::delete($this->TABLE)
                ->where('id', $this->ID)
                ->run($this->DATALAYER);

            $oldId = $this->ID;
            $this->ID = null;

            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheRemove($oldId);

            if (is_callable($onDelete))
                $onDelete($this);
        } else {
            Log::changeScope('driver.delete.aborted', '[#].[#]([#]) aborted in _onDelete', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]);
        }
    }

    final function __get($name)
    {
        if ($name == 'id') return $this->ID;

        if ($name == 'idKey') return $this->idKey();

        if (!isset($this->FIELD[$name]))
            throw new Error("Field [$name] not exists in [$this->TABLE]");

        return $this->FIELD[$name];
    }

    final function __call($name, $arguments)
    {
        if (!isset($this->FIELD[$name]))
            throw new Error("Field [$name] not exists in [$this->TABLE]");

        if (!count($arguments))
            return $this->FIELD[$name]->get();

        $this->FIELD[$name]->set(...$arguments);
        return $this;
    }

    protected function _onCreate() {}

    protected function _onUpdate() {}

    protected function _onDelete() {}
}
