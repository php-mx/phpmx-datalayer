# phpmx db

---

Grupo de comandos para operações de banco de dados

---

## db.export

Exporta o conteúdo de uma ou mais tabelas do banco de dados para um arquivo JSON.

```sh
php mx db export <dbName> <tabelas>
```

- `<dbName>`: Nome da conexão (padrão: main)
- `<tabelas>`: Lista de tabelas separadas por vírgula ou \* para todas (padrão: \*)

Exemplo:

```sh
php mx db export main user,log
php mx db export main *
```

---

## db.import

Importa dados de um arquivo JSON para uma ou mais tabelas do banco de dados.

```sh
php mx db import <dbName> <tabelas>
```

- `<dbName>`: Nome da conexão (padrão: main)
- `<tabelas>`: Lista de tabelas separadas por vírgula ou \* para todas (padrão: \*)

Exemplo:

```sh
php mx db import main user,log
php mx db import main *
```

---

## db.driver

Gera ou atualiza os drivers de banco de dados do projeto.

```sh
php mx db driver <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx db driver main
```

---

## db.map

Gera ou atualiza o mapeamento do banco de dados.

```sh
php mx db map <dbName>
```

- `<dbName>`: Nome da conexão (padrão: main)

Exemplo:

```sh
php mx db map main
```
