<?php

namespace PhpMx\Datalayer\Driver;

use PhpMx\Datalayer\Query;
use PhpMx\Datalayer\Driver\Field\FIdx;
use Error;
use PhpMx\Log;

/**
 * @property int|null $id Chave de identificação numérica do registro.
 * @ignore
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
    protected bool $UNDELETE = false;

    /** @ignore */
    function __construct(array $scheme)
    {
        $this->FIELD = array_merge([
            '_created' => new \PhpMx\Datalayer\Driver\Field\FTimestamp('_created', false, 'CURRENT_TIMESTAMP', []),
            '_updated' => new \PhpMx\Datalayer\Driver\Field\FTimestamp('_updated', true, null, []),
            '_deleted' => new \PhpMx\Datalayer\Driver\Field\FTimestamp('_deleted', true, null, []),
        ], $this->FIELD);

        $this->_arraySet($scheme);

        $this->ID = $scheme['id'] ?? null;
        $this->INITIAL = $this->__insertValues();

        if ($this->_checkInDb()) {
            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheSet($this->ID, $this);
        }
    }

    /**
     * Retorna a chave de identificação numérica do registro.
     * @return int|null
     */
    final function id(): ?int
    {
        return $this->ID;
    }

    /**
     * Retorna a chave de identificação cifrada (idKey) do registro.
     * @return string|null
     */
    final function idKey(): ?string
    {
        if (!$this->_checkInDb()) return null;
        $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
        $tableMethod = strToCamelCase($this->TABLE);
        return $drvierClass::${$tableMethod}->idToIdkey($this->id);
    }

    /**
     * Retorna o momento em que o registro foi criado.
     * @return string|null
     */
    final function _created(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->FIELD['_created']->get();
    }

    /**
     * Retorna o momento da última atualização do registro.
     * @return string|null
     */
    final function _updated(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->FIELD['_updated']->get();
    }

    /**
     * Retorna o momento da última mudança do registro (criação ou atualização).
     * @return string|null
     */
    final function _changed(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->_updated() ? $this->_updated() : $this->_created();
    }

    /**
     * Retorna o momento em que o registro foi marcado como removido.
     * @return string|null
     */
    final function _deleted(): ?string
    {
        if (!$this->_checkInDb()) return null;
        return $this->FIELD['_deleted']->get();
    }

    /**
     * Retorna o valor do esquema de um campo do registro.
     * @param string $field Nome do campo.
     */
    final function _schemeValue(string $field)
    {
        $field = str_starts_with($field, '_') ? $field : strToCamelCase($field);
        return method_exists($this, "_scheme_$field") ? $this->{"_scheme_$field"}() : $this->_array($field)[$field];
    }

    /**
     * Retorna os campos solicitados do registro tratados em forma de array de esquema.
     * @param array $fields Campos a retornar, podendo ser strings, arrays associativos [alias => callable] ou callables.
     * @return array
     */
    final function _scheme(array $fields): array
    {
        $scheme = [];

        foreach ($fields as $pos => $field) {

            $fieldName = is_numeric($pos) ? $field : $pos;
            $schemeWraper =  is_numeric($pos) ? fn($record) => $record->_schemeValue($field) : $field;
            $schemeWraper = !is_callable($schemeWraper) ? fn($record) => $schemeWraper : $schemeWraper;

            $scheme[$fieldName] = $schemeWraper($this);
        }


        if (!$this->_checkInDb()) {
            if (isset($scheme['_changed'])) $scheme['_changed'] = null;
            if (isset($scheme['_created'])) $scheme['_created'] = null;
            if (isset($scheme['_updated'])) $scheme['_updated'] = null;
            if (isset($scheme['_deleted'])) $scheme['_deleted'] = null;
        }

        return $scheme;
    }

    /**
     * Retorna todos os campos e esquemas personalizados do registro em forma de array.
     * @param array $fieldsRemove Campos a excluir do retorno.
     * @return array
     */
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

    /**
     * Marca o registro como ativo na tabela correspondente.
     * @return static
     */
    final function _makeActive(): static
    {
        $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
        $tableMethod = strToCamelCase($this->TABLE);

        $drvierClass::${$tableMethod}->active($this);
        return $this;
    }

    /**
     * Retorna os campos do registro em forma de array.
     * @param string ...$fields Campos a retornar (opcional, retorna todos por padrão).
     */
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

    /**
     * Define os valores dos campos do registro com base em um array.
     * @param mixed $scheme Array associativo [campo => valor].
     * @return static
     */
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

    /**
     * Aplica um array de mudanças incrementais aos campos do registro.
     * @param array $changes Array de mudanças a aplicar.
     * @return static
     */
    final function _arrayChange(array $changes): static
    {
        $array = $this->_array();
        applyChanges($array, $changes);
        $this->_arraySet($array);
        return $this;
    }

    /**
     * Verifica se o registro existe no banco de dados (id > 0).
     * @return bool
     */
    final function _checkInDb(): bool
    {
        return !is_null($this->id()) && $this->id() > 0;
    }

    /**
     * Verifica se algum dos campos fornecidos foi alterado desde o último carregamento.
     * @param string ...$fields Campos a verificar (opcional, verifica todos por padrão).
     * @return bool
     */
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

    /**
     * Verifica se o registro pode ser salvo no banco de dados (id não nulo).
     * @return bool
     */
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

    /**
     * Prepara o registro para ser marcado como excluído no próximo _save().
     * @param bool $delete Se verdadeiro marca para exclusão.
     * @return static
     */
    final function _delete(bool $delete): static
    {
        $this->DELETE = $delete;
        return $this;
    }

    /**
     * Prepara o registro para ser desmarcado como excluído no próximo _save().
     * @param bool $undelete Se verdadeiro marca para recuperação.
     * @return static
     */
    final function _undelete(bool $undelete): static
    {
        $this->UNDELETE = $undelete;
        return $this;
    }

    /**
     * Salva o registro no banco de dados (create, update, delete ou undelete conforme o estado).
     * @param bool $forceUpdate Se verdadeiro força o update mesmo sem alterações detectadas.
     * @return static
     */
    final function _save(bool $forceUpdate = false): static
    {
        Log::add(
            'driver.save',
            prepare("[#].[#]", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]),
            function () use ($forceUpdate) {
                if ($this->_checkSave()) {
                    match (true) {
                        $this->DELETE => $this->__runDelete(),
                        $this->UNDELETE => $this->__runUndelete(),
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

    /**
     * Salva todos os campos FIdx com registros carregados antes de persistir o registro principal.
     */
    final protected function __runSaveIdx()
    {
        foreach ($this->FIELD as &$field) {
            if (is_class($field, FIdx::class) && $field->_checkLoad() && $field->_checkSave())
                if (!$field->id ||  $field->id != $this->ID || !is_class($field->_record(), $this::class))
                    $field->_save();
        }
    }

    /**
     * Executa a criação do registro no banco de dados e atualiza o ID e cache.
     * Dispara o hook _onCreate(); retorna false ou callable para abortar/pós-processamento.
     */
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

    /**
     * Executa a atualização do registro no banco de dados, enviando apenas os campos alterados.
     * Dispara o hook _onUpdate(); retorna false ou callable para abortar/pós-processamento.
     * @param bool $forceUpdate Se verdadeiro força o UPDATE mesmo sem alterações detectadas.
     */
    final protected function __runUpdate(bool $forceUpdate)
    {
        Log::changeScope('driver.update', prepare("[#].[#]([#])", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]));
        $this->__runSaveIdx();
        if ($forceUpdate || $this->_checkChange()) {
            $onUpdate = $this->_onUpdate() ?? null;
            if ($onUpdate ?? true) {
                $dif = $this->__insertValues(true);

                foreach ($dif as $name => $value)
                    if ($value == $this->INITIAL[$name])
                        unset($dif[$name]);

                $this->FIELD['_updated']->set(true);
                $dif['_updated'] = $this->FIELD['_updated']->get();

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

    /**
     * Executa a exclusão lógica do registro (soft-delete) preenchendo o campo _deleted.
     * Dispara o hook _onDelete(); retorna false ou callable para abortar/pós-processamento.
     */
    final protected function __runDelete()
    {
        Log::changeScope('driver.delete', prepare("[#].[#]([#])", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]));
        $this->__runSaveIdx();
        $onDelete = $this->_onDelete() ?? null;
        if ($onDelete ?? true) {
            $this->FIELD['_deleted']->set(true);

            $dif = ['_deleted' => $this->FIELD['_deleted']->get()];

            Query::update($this->TABLE)
                ->where('id', $this->ID)
                ->values($dif)
                ->run($this->DATALAYER);

            $this->INITIAL['_deleted'] = $dif['_deleted'];

            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheRemove($this->ID);

            if (is_callable($onDelete))
                $onDelete($this);
        } else {
            Log::changeScope('driver.delete.aborted', '[#].[#]([#]) aborted in _onDelete', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]);
        }
    }

    /**
     * Restaura um registro excluído logicamente limpando o campo _deleted.
     * Dispara o hook _onUndelete(); retorna false ou callable para abortar/pós-processamento.
     */
    final protected function __runUndelete()
    {
        Log::changeScope('driver.undelete', prepare("[#].[#]([#])", [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE), $this->id()]));
        $this->__runSaveIdx();
        $onUndelete = $this->_onUndelete() ?? null;
        if ($onUndelete ?? true) {
            $this->FIELD['_deleted']->set(null);

            $dif = ['_deleted' => $this->FIELD['_deleted']->get()];

            Query::update($this->TABLE)
                ->where('id', $this->ID)
                ->values($dif)
                ->run($this->DATALAYER);

            $this->INITIAL['_deleted'] = $dif['_deleted'];

            $drvierClass = 'Model\\' . strToPascalCase("db $this->DATALAYER") . '\\' . strToPascalCase("db $this->DATALAYER");
            $tableMethod = strToCamelCase($this->TABLE);
            $drvierClass::${$tableMethod}->__cacheSet($this->ID, $this);

            if (is_callable($onUndelete))
                $onUndelete($this);
        } else {
            Log::changeScope('driver.undelete.aborted', '[#].[#]([#]) aborted in _onUndelete', [strToPascalCase("db $this->DATALAYER"), strToCamelCase($this->TABLE)]);
        }
    }

    /**
     * Acesso mágico a propriedades: retorna o ID, idKey ou o objeto Field pelo nome.
     * @param string $name Nome da propriedade ('id', 'idKey' ou nome de campo).
     * @return mixed
     */
    final function __get($name)
    {
        if ($name == 'id') return $this->ID;

        if ($name == 'idKey') return $this->idKey();

        if (!isset($this->FIELD[$name]))
            throw new Error("Field [$name] not exists in [$this->TABLE]");

        return $this->FIELD[$name];
    }

    /**
     * Chamada mágica de método: sem argumentos retorna o valor do campo; com argumentos define o valor.
     * @param string $name Nome do campo.
     * @param array $arguments Argumentos passados na chamada.
     * @return mixed|static Valor do campo ou $this para encadeamento.
     */
    final function __call($name, $arguments)
    {
        if (!isset($this->FIELD[$name]))
            throw new Error("Field [$name] not exists in [$this->TABLE]");

        if (!count($arguments))
            return $this->FIELD[$name]->get();

        $this->FIELD[$name]->set(...$arguments);
        return $this;
    }

    /**
     * Hook chamado antes de criar o registro no banco de dados.
     * Retorne false para abortar a criação, ou um callable para executar após a criação.
     */
    protected function _onCreate() {}

    /**
     * Hook chamado antes de atualizar o registro no banco de dados.
     * Retorne false para abortar a atualização, ou um callable para executar após a atualização.
     */
    protected function _onUpdate() {}

    /**
     * Hook chamado antes de excluir logicamente o registro.
     * Retorne false para abortar a exclusão, ou um callable para executar após a exclusão.
     */
    protected function _onDelete() {}

    /**
     * Hook chamado antes de restaurar um registro excluído logicamente.
     * Retorne false para abortar a restauração, ou um callable para executar após a restauração.
     */
    protected function _onUndelete() {}
}
