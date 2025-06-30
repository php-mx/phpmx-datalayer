# Datalayer - Compatível - executeQuery

O método `executeQuery` é uma interface direta para executar comandos SQL no seu banco de dados, seja ele MySQL, SQLite ou outro suportado. Ao usar este método, você é totalmente responsável por escrever as queries corretamente, considerando as diferenças de sintaxe e recursos entre os bancos (por exemplo, comandos que existem em MySQL mas não em SQLite, e vice-versa).

O `executeQuery` não faz validação, conversão ou adaptação automática das queries. Ele apenas repassa o comando para o banco e retorna o resultado. Por isso, é recomendado utilizar o Query Builder do datalayer sempre que possível, pois ele abstrai essas diferenças e facilita a portabilidade do código entre bancos distintos. [Veja como usar o Query Builder](../queryBuilder.md)

Este método é indicado para situações em que você precisa executar uma query muito específica, personalizada para o seu banco, ou quando deseja controle total sem nenhum tipo de automação. Use apenas quando souber exatamente o que está fazendo.

---

## Como utilizar o executeQuery

Após obter a instância do banco:

```php
use PhpMx\Datalayer;
$db = Datalayer::get('main');
```

Você pode executar comandos SQL diretamente:

### SELECT

```php
$result = $db->executeQuery('SELECT * FROM user WHERE ativo = ?', [1]);
foreach ($result as $row) {
    // ...
}
```

### INSERT

```php
$id = $db->executeQuery('INSERT INTO user (nome, ativo) VALUES (?, ?)', ['João', 1]);
// $id contém o ID do novo registro
```

### UPDATE

```php
$ok = $db->executeQuery('UPDATE user SET ativo = ? WHERE id = ?', [0, 42]);
// $ok será true em caso de sucesso
```

### DELETE

```php
$ok = $db->executeQuery('DELETE FROM user WHERE id = ?', [42]);
// $ok será true em caso de sucesso
```

> O método aceita parâmetros para queries preparadas, aumentando a segurança contra SQL Injection.

---
