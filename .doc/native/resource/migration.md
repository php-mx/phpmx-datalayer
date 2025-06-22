# Datalayer - Nativo - migration

Migrations são arquivos que descrevem, de forma incremental, todas as alterações de estrutura do banco de dados da sua aplicação: criação e remoção de tabelas, campos, índices, relacionamentos, etc. Elas permitem versionar, automatizar e garantir a evolução consistente do schema, tornando o banco de dados totalmente controlado pelo datalayer.

No modo nativo, todas as mudanças estruturais devem ser feitas via migrations. O datalayer executa essas migrations para criar, atualizar ou reverter o banco de dados conforme a evolução do seu projeto.

---

## Criando uma migration

Para criar um novo arquivo de migration, utilize o comando abaixo no terminal:

```sh
php mx create migration <nome>
```

- `<nome>`: Nome da migration a ser criada (ex: user)

Exemplo:

```sh
php mx create migration user
```

Caso utilize uma conexão que não seja a main, adicione o nome da conexão a migration

```sh
php mx create migration minh_conexao.user
```

---

## Estrutura de um arquivo de migration

Cada migration gerada segue um padrão de classe PHP com dois métodos principais:

- `up()`: Executa as alterações para aplicar a migration (ex: criar tabelas, adicionar campos).
- `down()`: Reverte as alterações feitas pelo `up()` (ex: remover tabelas, desfazer mudanças).

---

## Métodos principais da migration

```php
$this->table(string $nome,?string $comentario = null): SchemeTable
```

Retorna (ou cria) uma tabela do banco de dados para ser manipulada na migration.

- **nome**: Nome da tabela no banco de dados.
- **comentario** (opcional): Texto descritivo sobre a tabela. Esse comentário pode ser utilizado para documentação, geração de código ou facilitar a manutenção futura.

Exemplo:

```php
// Cria ou altera a tabela 'user' com um comentário descritivo
$this->table('user', 'Usuários do sistema');

// Cria a tabela e já define os campos
$this->table('user', 'Usuários do sistema')->fields([
    $this->fString('name', 'Nome do usuário'),
    $this->fEmail('email', 'Email para acesso e contato')->indexUnique(true)
]);

// Marca a tabela para remoção
$this->table('user')->drop();
```

> O uso do comentário é opcional, mas recomendado para projetos que valorizam documentação e clareza.

---

## Tipos de campo disponíveis na migration

Os campos de uma tabela são definidos por métodos helpers da própria migration, que retornam objetos já configurados para cada tipo. Todos aceitam métodos encadeáveis para configuração avançada.

- **fBoolean**: Campo booleano (true/false)
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
- **fCode**: Código/hash
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.
- **fConfig**: Configuração serializada
  - `default($valor)`: Define o valor padrão do campo.
- **fEmail**: Email
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.
- **fFloat**: Número decimal
  - `decimal($int)`: Casas decimais.
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `max($int)`: Valor máximo.
  - `min($int)`: Valor mínimo.
  - `null($bool)`: Permite valor nulo.
  - `round($int)`: Forma de arredondamento.
  - `size($int)`: Define o tamanho máximo.
- **fHash**: Hash criptográfico
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.
- **fIds**: Lista de IDs (relacionamento N:N)
  - `datalayer($nome)`: Define o datalayer de referência.
  - `default($valor)`: Define o valor padrão do campo.
  - `table($nome)`: Define a tabela de referência.
- **fIdx**: ID de referência (relacionamento 1:N)
  - `datalayer($nome)`: Define o datalayer de referência.
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.
  - `table($nome)`: Define a tabela de referência.
- **fInt**: Número inteiro
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `max($int)`: Valor máximo.
  - `min($int)`: Valor mínimo.
  - `null($bool)`: Permite valor nulo.
  - `round($int)`: Forma de arredondamento.
  - `size($int)`: Define o tamanho máximo.
- **fJson**: Dados em JSON
  - `default($valor)`: Define o valor padrão do campo.
- **fLog**: Log de alterações
  - `default($valor)`: Define o valor padrão do campo.
- **fString**: Texto curto
  - `crop($bool)`: Corta texto acima do tamanho.
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.
  - `size($int)`: Define o tamanho máximo.
- **fText**: Texto longo
  - `default($valor)`: Define o valor padrão do campo.
- **fTime**: Data/hora (timestamp)
  - `default($valor)`: Define o valor padrão do campo.
  - `index($bool)`: Cria índice simples.
  - `indexUnique($bool)`: Cria índice único.
  - `null($bool)`: Permite valor nulo.

**Disponíveis em todos os tipos de campo:**

- `drop()`: Marca o campo para remoção.
- `comment($texto)`: Define o comentário do campo.

---

## Exemplo de migrations encadeadas e relacionamento entre tabelas

A seguir, veja dois exemplos de migrations que podem ser aplicadas em sequência, mostrando a evolução do schema e a criação de um relacionamento entre tabelas:

### 1. Migration de criação da tabela `user`

```php
<?php
/** Migration 17506154182805275_user */

return new class extends \PhpMx\Datalayer\Migration
{
    function up()
    {
        $this->table('user', 'Usuários do sistema')->fields([
            $this->fString('name', 'Nome do usuário')->size(100)->index(true),
            $this->fEmail('email', 'E-mail de acesso')->indexUnique(true),
            $this->fInt('age', 'Idade')->min(0)->max(150),
            $this->fBoolean('active', 'Usuário ativo')->default(true)
        ]);
    }

    function down()
    {
        $this->table('user')->drop();
    }
};
```

### 2. Migration de criação da tabela `user_group` e relacionamento com `user`

```php
<?php
/** Migration 17506154182805276_group_user */

return new class extends \PhpMx\Datalayer\Migration
{
    function up()
    {
        $this->table('user_group', 'Grupos de usuários')->fields([
            $this->fString('name', 'Nome do grupo')->size(50)->indexUnique(true)
        ]);

        // Adiciona o relacionamento na tabela user
        $this->table('user')->fields([
            $this->fIdx('user_group', 'Grupo do usuário')
        ]);
    }

    function down()
    {
        // Remove apenas o campo de relacionamento
        $this->table('user')->field('user_group')->drop();
        $this->table('user_group')->drop();
    }
};
```

---

## Executando as migrations no banco de dados

Após criar suas migrations, utilize os comandos abaixo no terminal para aplicar ou reverter as alterações no banco:

- `php mx migration.up` — Executa a proxima migration da fila
- `php mx migration.down` — Desfaz as alterações da ultima migration aplicada
- `php mx migration.run` — Executa todas as migrations pendentes na fila
- `php mx migration.clean` — Desfaz as alterações da ultima migration aplicada

Se você estiver usando uma conexão diferente do banco padrão ("main"), basta informar o nome da conexão ao final do comando. Por exemplo:

- `php mx migration.up minha_conexao`
- `php mx migration.down minha_conexao`
- `php mx migration.run minha_conexao`
- `php mx migration.clean minha_conexao`

> Use sempre esses comandos para garantir o versionamento e a integridade do schema do banco de dados.
