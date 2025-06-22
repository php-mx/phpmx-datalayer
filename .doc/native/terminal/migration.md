# phpmx migration

---

Grupo de comandos para gerenciamento de migrations no projeto PHPMX.

---

## migration.up

Executa a próxima migration pendente, aplicando apenas uma alteração por vez no banco de dados.

```sh
php mx migration up <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx migration up main
```

---

## migration.down

Desfaz a última migration aplicada no banco de dados, revertendo uma alteração por vez.

```sh
php mx migration down <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx migration down main
```

---

## migration.run

Executa todas as migrations pendentes, aplicando todas as alterações no banco de dados de uma vez.

```sh
php mx migration run <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx migration run main
```

---

## migration.clean

Desfaz todas as migrations aplicadas, revertendo o banco de dados ao estado inicial (rollback total).

```sh
php mx migration clean <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx migration clean main
```
