# Datalayer - Compatível - Query Builder

O Query Builder do datalayer oferece uma interface orientada a objetos para construir queries SQL de forma segura, legível e portável entre diferentes bancos de dados. Ele suporta os principais comandos: SELECT, INSERT, UPDATE e DELETE, cada um com sua própria classe dedicada. Com o Query Builder, você pode montar consultas complexas sem se preocupar com diferenças de sintaxe entre MySQL, SQLite ou outros bancos suportados, além de evitar erros comuns de concatenação de strings e SQL Injection.

Para utilizar o Query Builder, é necessário importar a classe estática `Query`, localizada em `\PhpMx\Datalayer`:

```php
use PhpMx\Datalayer\Query;
```

A classe `Query` fornece quatro métodos principais para construção de queries:

- `Query::select()`
- `Query::insert()`
- `Query::update()`
- `Query::delete()`

Cada método retorna um objeto configurável para montar a consulta desejada. Os exemplos de uso de cada método serão detalhados nas seções seguintes.

---

## Select

Utilizada para buscar registros no banco de dados.

Para usar, basta chamar `Query::select('nome_da_tabela')` e encadear os métodos desejados para montar a consulta. Os principais métodos disponíveis são:

- `fields($campos)`: Define os campos a serem retornados (array, string ou múltiplos argumentos). Se não usar, retorna todos.
- `where($campo, $valor)`: Adiciona condições WHERE (pode ser chamado múltiplas vezes).
- `whereIn($campo, $arrayDeIds)`: WHERE ... IN (...)
- `whereNull($campo)`: WHERE ... IS NULL
- `limit($quantidade)`: Limita o número de resultados.
- `page($pagina, $limite)`: Paginação (define offset e limite).
- `order($campo, $asc)`: Ordenação (ascendente ou descendente).
- `group($campo)`: Agrupamento (GROUP BY).

Para executar a consulta, use o método `run('dbName')`. O retorno será sempre um array de resultados.

Exemplo básico de uso:

```php
use PhpMx\Datalayer\Query;

$result = Query::select('user')
    ->fields('id', 'nome', 'email')
    ->where('ativo', 1)
    ->order('nome')
    ->limit(10)
    ->run('main');

// $result será um array de arrays associativos com os dados encontrados
```

---

## Insert

Utilizada para inserir novos registros em uma tabela do banco de dados.

Para usar, chame `Query::insert('nome_da_tabela')` e utilize o método `values($dados)` para definir os campos e valores a serem inseridos. Você pode passar um array associativo com os nomes dos campos e seus respectivos valores.

Para executar a inserção, use o método `run('dbName')`. O retorno será o ID do novo registro inserido (quando suportado pelo banco).

Exemplo básico de uso:

```php
use PhpMx\Datalayer\Query;

$id = Query::insert('user')
    ->values(['nome' => 'João', 'email' => 'joao@email.com', 'ativo' => 1])
    ->run('main');

// $id conterá o ID do novo registro inserido
```

---

## Update

Utilizada para atualizar registros existentes em uma tabela do banco de dados.

Para usar, chame `Query::update('nome_da_tabela')`, defina os campos e valores a serem atualizados com o método `values($dados)` e adicione as condições com `where($campo, $valor)` (ou outros métodos de filtro). É importante sempre definir um critério de filtro para evitar atualizar todos os registros da tabela.

Para executar a atualização, use o método `run('dbName')`. O retorno será `true` em caso de sucesso.

Exemplo básico de uso:

```php
use PhpMx\Datalayer\Query;

$ok = Query::update('user')
    ->values(['ativo' => 0])
    ->where('id', 42)
    ->run('main');

// $ok será true em caso de sucesso
```

---

## Delete

Utilizada para remover registros de uma tabela do banco de dados.

Para usar, chame `Query::delete('nome_da_tabela')` e defina as condições de remoção com `where($campo, $valor)` (ou outros métodos de filtro). É fundamental sempre definir um critério de filtro para evitar deletar todos os registros da tabela.

Para executar a exclusão, use o método `run('dbName')`. O retorno será `true` em caso de sucesso.

Exemplo básico de uso:

```php
use PhpMx\Datalayer\Query;

$ok = Query::delete('user')
    ->where('id', 42)
    ->run('main');

// $ok será true em caso de sucesso
```
