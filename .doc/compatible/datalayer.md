# Datalayer - Compatível

> O modo compatível do datalayer existe para atender cenários onde não é possível ou desejável migrar o controle do banco de dados para o ecossistema PHPMX. Neste modo, o datalayer atua apenas como consumidor do banco: ele permite leitura e escrita em bancos externos, legados ou compartilhados, sem impor regras de estrutura, migrations, índices ou validações automáticas. Toda a responsabilidade pela integridade, manutenção e evolução do banco é do próprio usuário ou de outros sistemas. Recursos avançados do datalayer, como automação, consistência e relacionamento, não estão disponíveis neste modo. Use o modo compatível apenas quando não for possível adotar o uso nativo.

O modo compatível do datalayer foi projetado para oferecer uma transição suave para aqueles que estão acostumados com o funcionamento tradicional de bancos de dados, permitindo que continuem a operar da mesma forma, mas com a possibilidade de migrar gradualmente para os novos recursos do PHPMX no futuro.

Após configurar sua conexão nas variáveis de ambiente, utilize a classe estática **Datalayer** para acessar o objeto de banco:

```php
use PhpMx\Datalayer;
$db = Datalayer::get('main');
```

## Armazenando configurações no banco

O DataLayer permite salvar e recuperar variáveis de configuração do seu projeto diretamente no banco, usando os métodos **setConfig** e **getConfig**. Ao utilizar esses métodos, será criada automaticamente uma tabela chamada `__config` no seu banco, exclusiva para armazenar essas variáveis.

Você pode usar isso para guardar parâmetros, chaves, flags ou qualquer configuração que precise ser persistida e acessada pelo seu projeto.

```php
// Salvar uma configuração
$db->setConfig('nomeDaConfig', 'valor');

// Recuperar uma configuração
$db->getConfig('nomeDaConfig');
```

## Executando queries

O método `executeQuery` permite executar comandos SQL diretamente no banco, seja passando uma string SQL ou um objeto BaseQuery.

- [executeQuery](./resource/executeQuery.md)
- [queryBuilder](./resource/queryBuider.md)
