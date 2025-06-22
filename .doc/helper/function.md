# Funções

Este documento lista as funções globais utilitárias disponíveis no PHPMX, definidas em `/helper/function`.

---

## is_idKey

Verifica se uma variavel é um idKey

```php
is_idKey(mixed $idKey): bool
```

## idKeyType

Retorna o tipo de um idKey

```php
idKeyType(string $idKey): ?string
```

## idKeyId

Retorna o id de um idKey

```php
idKeyId(string $idKey): ?int
```

---

## syncIds

Sincroniza dois registros com IDSs cruzados

```php
syncIds(int $recordId, array $oldValues, array $newValues, Table $table, string $fieldName): void
```
