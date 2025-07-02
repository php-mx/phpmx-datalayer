# Datalayer - Nativo - driver

Após rodar todas as suas migrations, é hora de criar o driver e a camada de model do seu sistema. O driver é responsável por mapear toda a estrutura do banco de dados, permitindo que o datalayer acesse e manipule os dados de forma eficiente e tipada.

> O driver gerado não é um ORM tradicional. Ele não usa introspecção em tempo de execução nem reflection — tudo é gerado com base nas migrations e está tipado em tempo real.

A criação do driver é feita automaticamente pelo comando abaixo:

```sh
php mx db.driver <nome_da_conexao>
```

- `<nome_da_conexao>`: Nome da conexão/banco de dados que você deseja gerar o driver (ex: main, minha_conexao, etc).

Esse comando irá analisar todas as migrations aplicadas e gerar os arquivos de driver e model correspondentes para a conexão informada.

> Certifique-se de que todas as migrations estejam aplicadas antes de rodar este comando!

## Estrutura dos arquivos de driver

Ao rodar o comando de geração do driver, os arquivos são criados automaticamente dentro do namespace `Model/DB<NomeDaConexao>`, onde `<NomeDaConexao>` é o nome da sua conexão em PascalCase (ex: DbMain, DbMinhaConexao).

A estrutura gerada é:

- `Model/Db<NomeDaConexao>/Db<NomeDaConexao>.php`: Porta de entrada do driver. Responsável por inicializar e expor o acesso ao banco.
- `Model/Db<NomeDaConexao>/driver/`: Pasta (gitignorada) com arquivos internos do driver. Não deve ser alterada manualmente.
- `Model/Db<NomeDaConexao>/record/`: Contém as classes de registro (um para cada tabela), representando cada linha do banco como objeto.
- `Model/Db<NomeDaConexao>/table/`: Contém as classes de tabela, responsáveis pelas operações e queries de cada tabela.

Tudo isso é gerado automaticamente e pronto para uso.

## Arquivo principal DbMain

O arquivo `Model/Db<NomeDaConexao>/Db<NomeDaConexao>.php` é a porta de entrada para acessar o banco via datalayer. Ele centraliza a inicialização e o acesso às tabelas e registros, herdando toda a lógica do driver gerado.

- Para cada tabela do banco, o DbMain implementa um método estático correspondente, permitindo acessar registros diretamente.
- Também expõe objetos estáticos para cada tabela, facilitando operações como selects e manipulação de dados.

**Exemplo de uso:**

```php
use Model\DbMain\DbMain;

// Buscar um registro específico da tabela 'user' pelo ID
$user = DbMain::user(1);

// Acessar o objeto da tabela 'user' para realizar queries customizadas
$tableUser = DbMain::$user;

// Exemplo de uso do objeto da tabela para buscar todos os usuários ativos
$ativos = DbMain::$user->get('status', 'ativo');
```

- O namespace segue o padrão `Model\Db<NomeDaConexao>` (ex: `Model\DbMain`).
- A classe herda do driver principal da conexão.

## Arquivos de tabela (table)

O arquivo `Model/Db<NomeDaConexao>/table/Table<NomeDaTabela>.php` representa a tabela do banco e centraliza as operações e queries sobre ela. Por padrão, as classes Table são geradas vazias, herdando toda a lógica do driver correspondente.

- Cada tabela implementa métodos prontos para uso, como:
  - `getOne`, `getAll`, `active`, `count`, `check`, `getOneScheme`, `getOneSchemeAll`, `getOneKey`, entre outros.
- Os métodos `getOne` e `getAll` também aceitam um objeto Query Builder como parâmetro, permitindo buscas avançadas e customizadas de forma segura (os hooks e validações do driver continuam sendo executados normalmente).
- Você pode implementar métodos customizados na sua classe Table para facilitar buscas ou operações específicas. Se não precisar de buscas diferentes, basta usar os métodos já implementados.
- O objeto estático da tabela, retornado pelo DbMain, permite acessar todos esses métodos facilmente.

**Exemplo de uso:**

```php
use Model\DbMain\DbMain;
use PhpMx\Datalayer\Query;

// Buscar um registro específico pelo ID
$user = DbMain::$user->getOne(1);

// Buscar todos os registros da tabela
$usuarios = DbMain::$user->getAll();

// Contar registros com determinado critério
$total = DbMain::$user->count(['status' => 'ativo']);

// Verificar se existe pelo menos um registro com determinado critério
$existe = DbMain::$user->check(['email' => 'teste@exemplo.com']);

// Buscar esquema completo de um registro
$esquema = DbMain::$user->getOneSchemeAll([], 1);

// Buscar esquema completo de todos os registros
$esquemas = DbMain::$user->getAllSchemeAll();

// Buscar por idKey
$registro = DbMain::$user->getOneKey('idKey');

// Buscar usando Query Builder
$query = Query::select()->where('status', 'ativo')->order('nome');
$usuario = DbMain::$user->getOne($query);
$usuarios = DbMain::$user->getAll($query);
```

- Implemente métodos customizados na sua Table apenas se precisar de buscas ou operações específicas para aquela tabela.
- Se não precisar de customização, basta usar os métodos herdados do driver.

## Arquivos de registro (record)

Cada arquivo em `Model/Db<NomeDaConexao>/record/` representa um registro (linha) de uma tabela do banco. Ele herda do driver gerado automaticamente e permite customizar comportamentos específicos para cada registro.

- O namespace segue o padrão `Model\Db<NomeDaConexao>\Record` (ex: `Model\DbMain\Record`).
- A classe herda do driver correspondente à tabela.
- Os métodos `_onCreate`, `_onUpdate` e `_onDelete` podem ser implementados para adicionar lógica customizada ao criar, atualizar ou remover registros
- Os métodos `_onCreate`, `_onUpdate` e `_onDelete` podem retornar `false` para cancelar a operação, ou lançar uma exceção para impedir a ação. Também podem retornar uma função (closure) para executar uma ação após o update/delete, caso necessário.

```php
<?php
namespace Model\DbMain\Record;

class User extends \Model\DbMain\Driver\DriverUser
{
    protected function _onCreate() {
        return function($record) {
            //Açõs pós criação
        };
    }
    protected function _onUpdate() {
        //Nenhuma ação
    }
    protected function _onDelete() {
        //Bloqueio de delete
        return false;
    }
}
```

> **Atenção:** Caso você utilize `executeQuery` ou o Query Builder diretamente para manipular registros, os hooks (`_onCreate`, `_onUpdate`, `_onDelete`) definidos no Record **não serão executados**. Para garantir que os hooks sejam disparados, utilize sempre os métodos do driver Table (como `getOne`, `getAll`, `_save`, etc). O uso direto de `executeQuery`/Query Builder é permitido, mas pula toda a lógica de hooks e validações do driver.

O arquivo `Model/Db<NomeDaConexao>/record/<NomeDaTabela>.php` representa um registro (linha) de uma tabela do banco. Ele herda do driver gerado automaticamente e já implementa diversos métodos úteis para manipulação do registro.

- Métodos principais já implementados:
  - `id()`: retorna a chave primária numérica do registro.
  - `idKey()`: retorna a chave cifrada (idKey) do registro, útil para buscas seguras.
  - `_created()`, `_updated()`, `_changed()`: retornam, respectivamente, o timestamp de criação, de última atualização e de última modificação (criação ou atualização) do registro.
  - `_save()`: salva o registro no banco de dados (cria ou atualiza automaticamente).
  - `_delete()`: marca o registro para exclusão permanente no próximo `_save()`.
  - `_checkInDb()`: verifica se o registro existe no banco.
  - `_array()`, `_arraySet()`, `_arrayChange()`: manipulação dos campos do registro em formato array.
  - Para cada campo da tabela, existe um método e um objeto correspondente, permitindo acessar e alterar valores de forma dinâmica (ex: `$user->name('Novo Nome')` para alterar o nome).

**Exemplo de uso:**

```php
use Model\DbMain\DbMain;

// Buscar um registro
$user = DbMain::$user->getOne(1);

// Acessar valores principais
$id = $user->id();
$idKey = $user->idKey();
$criado = $user->_created();
$atualizado = $user->_updated();
$modificado = $user->_changed();

// Alterar campos do registro
$user->name('Novo Nome')->email('novo@email.com');

// Salvar alterações
$user->_save();

// Deletar registro
$user->_delete(true)->_save();
```

- O Record já implementa todos os métodos essenciais para manipulação de registros.
- Para customizar comportamentos, implemente os métodos `_onCreate`, `_onUpdate` e `_onDelete` na sua classe Record.

## Scheme

O método `scheme` serve para exportar os dados de um registro de forma estruturada e segura, ideal para respostas de API, serialização ou integração com outros sistemas. Ele varre o objeto do registro e retorna apenas os campos desejados em formato de array.

- Por padrão, o `scheme` retorna apenas o `idKey` do registro, não expondo o ID real do banco.
- Você pode especificar quais campos deseja exportar, incluindo campos personalizados, como `id`, `name`, `email`, etc.
- O método pode ser usado tanto para um registro individual quanto para um array de registros (via `getSchemeAll`).

**Exemplo de uso:**

```php
// Buscar um registro
$user = DbMain::$user->getOne(1);

// Exportar apenas o idKey (padrão)
$dados = $user->_scheme([]); // [ 'idKey' => '...' ]

// Exportar campos específicos
$dados = $user->_scheme(['idKey', 'id', 'name', 'email']);
// [ 'idKey' => '...', 'id' => 1, 'name' => 'Fulano', 'email' => 'fulano@email.com' ]

// Exportar todos os campos padrão e customizados
$dados = $user->_schemeAll();

// Exportar um array de registros
$usuarios = DbMain::$user->getAll();
$dadosUsuarios = array_map(fn($u) => $u->_scheme(['idKey', 'name', 'email']), $usuarios);
```

### Manipulando e customizando o retorno do scheme

Você pode customizar o valor exportado de qualquer campo, inclusive criar campos virtuais que não existem no banco, apenas implementando um método protegido no seu Record com o padrão `get_<nomeDoCampo>`. Assim, ao exportar o scheme, esse método será chamado automaticamente para tratar o valor antes de exportar.

Isso permite, por exemplo, exportar um campo calculado como `idade`, mesmo que ele não exista no banco, apenas implementando:

```php
function get_idade() {
    return calcularIdade($this->dataNascimento());
}
```

Outro exemplo, para exportar o nome em maiúsculo:

```php
function get_name() {
    return strtoupper($this->name());
}
```

- O uso do `scheme` é recomendado para exportação de dados, respostas de API e integração, pois garante que apenas os campos desejados serão expostos.
- Para customizar o valor exportado de um campo (existente ou virtual), basta implementar o método `get_<nomeDoCampo>` no seu Record.
- Por padrão, o ID real do banco não é exposto, aumentando a segurança dos dados.

## Registro ativo (active)

Todo registro possui o método `_makeActive()`, que permite marcar aquele registro como "ativo" para a requisição atual. Isso é útil para cenários onde você precisa manter um registro em destaque ou como referência temporária durante o fluxo da aplicação.

- Ao chamar `$registro->_makeActive()`, aquele registro passa a ser considerado o ativo da tabela para a requisição.
- Para recuperar o registro ativo de uma tabela, basta usar `DbMain::$tabela->getOne(true)` ou `DbMain::$tabela->active()`.
- Só é possível ter um registro ativo por tabela por vez, e o status é mantido apenas durante a requisição atual.

**Exemplo de uso:**

```php
// Buscar e marcar um usuário como ativo
$user = DbMain::$user->getOne(1);
$user->_makeActive();

// Em outro ponto do código, recuperar o usuário ativo
$usuarioAtivo = DbMain::$user->getOne(true); // ou DbMain::$user->active();
```

- O recurso de registro ativo é útil para manter contexto temporário, como usuário logado, seleção corrente, etc.
- O registro ativo é por tabela e por requisição, não sendo persistido no banco.
