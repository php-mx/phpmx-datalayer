<?php

namespace PhpMx\Datalayer\Scheme;

use PhpMx\Datalayer;

/** @ignore */
class SchemeTable
{
    protected $name;
    protected $map;

    /** @var SchemeField[] */
    protected $fields = [];

    protected $isDroped = false;

    /** @ignore */
    function __construct(string $name, array $map = [], ?array $realMap = null)
    {
        $name = Datalayer::internalName($name);

        $realMap = $realMap ?? SchemeMap::TABLE_MAP;

        $map['comment'] = $map['comment'] ?? $realMap['comment'];
        $map['fields'] = $map['fields'] ?? $realMap['fields'];

        $this->name = $name;
        $this->map = $map;
    }

    /**
     * Marca ou desmarca a tabela para remoção ao aplicar o esquema.
     * @param bool $drop Se a tabela deve ser removida.
     * @return static
     */
    function drop(bool $drop = true): static
    {
        $this->isDroped = boolval($drop);
        return $this;
    }

    /**
     * Define o comentário descritivo da tabela.
     * @param string $comment Texto do comentário.
     * @return static
     */
    function comment(string $comment): static
    {
        $this->map['comment'] = $comment;
        return $this;
    }

    /**
     * Define ou altera múltiplos campos da tabela.
     * @param SchemeField[] $fields Array de objetos SchemeField a adicionar ou atualizar.
     * @return static
     */
    function fields(array $fields): static
    {
        foreach ($fields as $field)
            $this->fields[$field->getName()] = $field;

        return $this;
    }

    /**
     * Retorna o objeto de um campo da tabela, criando-o caso não exista.
     * @param string $fieldName Nome do campo.
     * @return SchemeField
     */
    function &field(string $fieldName): SchemeField
    {
        $fieldName = Datalayer::internalName($fieldName);

        $this->fields[$fieldName] = $this->fields[$fieldName] ?? new SchemeField($fieldName, $this->map['fields'][$fieldName] ?? []);

        return $this->fields[$fieldName];
    }

    /** @ignore */
    function getTableAlterMap(): bool|array
    {
        if ($this->isDroped)
            return false;

        $fields = [];
        foreach ($this->fields as $name => $field) {
            $field = $field->getMap();
            if ($field || isset($this->map['fields'][$name]))
                $fields[$name] = $field;
        }

        return [
            'comment' => $this->map['comment'],
            'fields' => $fields,
        ];
    }
}
