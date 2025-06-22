# Datalayer - Nativo

O modo nativo do datalayer é quando o controle total do banco de dados passa a ser feito pela sua aplicação, através do ecossistema PHPMX. Neste modo, o datalayer é responsável por toda a estrutura, consistência, automação e evolução do banco: migrations, validações, índices, relacionamentos e integrações são gerenciados automaticamente. O banco de dados pertence à aplicação, e todas as alterações devem ser feitas via recursos do datalayer, garantindo integridade e padronização. Esse é o modo recomendado para novos projetos ou para quem deseja aproveitar ao máximo a automação e segurança do PHPMX.

---

## Migrations

O primeiro passo para usar o modo nativo é configurar as migrations do seu projeto. Migrations são arquivos que descrevem, de forma incremental, as alterações de estrutura do banco de dados (criação de tabelas, campos, índices, relacionamentos, etc). Elas permitem versionar, automatizar e garantir a evolução consistente do schema.

- [migration](./resource/migration.md)

---

## Drivers

Após criar as migrations, o próximo passo é gerar os drivers do seu projeto. Drivers são a camada de model do datalayer: representam as tabelas, campos, relacionamentos e regras de negócio da sua aplicação. Eles são gerados e atualizados automaticamente a partir das migrations, por meio de um comando no terminal, garantindo que o código e o banco estejam sempre sincronizados.

Para entender como criar, atualizar e utilizar os drivers, consulte:

- [driver](./resource/driver.md)

---

## Documentação

- **Helper**

  - [function](./helper/function.md)

- **Terminal**

  - [create](./terminal/create.md)
  - [db](./terminal/db.md)
  - [migration](./terminal/migration.md)

---
