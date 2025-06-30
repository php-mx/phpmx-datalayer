# PHPMX - Datalayer

Camada de conexão com banco de dados para aplicações PHPMX.

---

## Dependência

- [phpmx-core](https://github.com/php-mx/phpmx-core)

---

## Instalação

A instalação pode ser feita em um projeto **vazio** ou junto ao **phpmx-core**, utilizando apenas dois comandos no terminal:

```bash
composer require phpmx/datalayer
./vendor/bin/mx install
```

Para verificar se tudo está pronto, execute:

```bash
php mx
```

---

## Configuração do Banco de Dados

Para começar a utilizar o **datalayer**, defina nas variáveis de ambiente a(s) conexão(ões) com o banco de dados.

O padrão das variáveis é:

```
DB_[NOME]_[CONFIG] = [VALOR]
```

### SQLite

```env
DB_MAIN_TYPE = sqlite
```

Os arquivos do banco ficam em **storage/sqlite** (já gitignorados). Por padrão, o datalayer usa o nome da conexão como nome do arquivo. Para definir um arquivo diferente:

```env
DB_MAIN_TYPE = sqlite
DB_MAIN_FILE = nomeDoArquivo
```

### MySQL / MariaDB

```env
DB_MAIN_TYPE = mysql
DB_MAIN_HOST = hostDaConexao
DB_MAIN_DATA = nomeDoBanco
DB_MAIN_USER = usuario
DB_MAIN_PASS = senha
```

Para alterar a porta padrão:

```env
DB_MAIN_TYPE = mysql
DB_MAIN_HOST = hostDaConexao
DB_MAIN_PORT = 3306
DB_MAIN_DATA = nomeDoBanco
DB_MAIN_USER = usuario
DB_MAIN_PASS = senha
```

### Múltiplos Bancos

Você pode declarar múltiplos bancos de dados para o projeto. Os bancos podem ser de tipos diferentes, mas **devem** ter nomes distintos.

```env
# Banco principal
DB_MAIN_TYPE = mysql
DB_MAIN_HOST = hostDaConexao
DB_MAIN_DATA = main
DB_MAIN_USER = usuario
DB_MAIN_PASS = senha

# Banco de log
DB_LOG_TYPE = sqlite

# Banco de suporte
DB_SECOND_TYPE = mysql
DB_SECOND_HOST = hostDaConexao
DB_SECOND_DATA = second
DB_SECOND_USER = usuario
DB_SECOND_PASS = senha
```

---

## Casos de uso

O DataLayer pode ser utilizado de duas formas, dependendo do nível de compatibilidade entre o banco de dados e a arquitetura PHPMX:

- **Modo compatível**: voltado para leitura/escrita em bancos existentes, externos ou compartilhados, sem controle estrutural.
- **Modo nativo**: recomendado para bancos dedicados ao projeto, utilizando toda a automação, validações e consistência do ecossistema.

| Recurso                               | Compatível | Nativo |
| ------------------------------------- | :--------: | :----: |
| Múltiplos bancos                      |     ✅     |   ✅   |
| Query builder                         |     ✅     |   ℹ️   |
| Migrations automáticas                |     ❌     |   ✅   |
| Exportação / Importação               |     ❌     |   ✅   |
| Controle de índices                   |     ❌     |   ✅   |
| Drivers automáticos                   |     ❌     |   ✅   |
| Actions (create/update/delete)        |     ❌     |   ✅   |
| Requisições inteligentes              |     ❌     |   ✅   |
| Relacionamento entre múltiplos bancos |     ❌     |   ✅   |
| Leitura via fontes externas           |     ✅     |   ✅   |
| Gravação via fontes externas          |     ✅     |   ⚠️   |

> ❌ **Sem suporte a recursos avançados no modo compatível:** não há controle de schema, migrations, índices ou relacionamento. O uso é manual e limitado. O DataLayer apenas consome o banco de dados.

> ℹ️ **Query Builder no modo nativo:** continua disponível, mas seu uso é desencorajado. A maior parte das queries são geradas automaticamente com base nos drivers e ações declaradas no projeto.

> ⚠️ **Atenção:** No uso nativo, o banco de dados é controlado pela aplicação. Alterações feitas fora da estrutura declarada (ex: scripts SQL, ferramentas gráficas, dumps manuais) podem comprometer a integridade dos dados e do sistema. Se for necessário modificar o banco diretamente, faça com consciência dos riscos.

Você pode combinar os dois estilos no mesmo projeto, usar um banco principal de forma nativa e um banco legado de forma compatível, apenas para leitura de dados antigos.

> **Importante:** Não existe uma configuração para "ativar" ou "desativar" o modo compatível ou nativo. O que define o modo é o conjunto de recursos que você utiliza: se usar apenas leitura/escrita manual, estará no modo compatível; se usar automações, validações e drivers, estará no modo nativo. Basta escolher os recursos conforme a necessidade de cada banco.

Veja a documentação de cada um dos modos do datalayer

- [Modo compativel](./.doc/compatible/datalayer.md)
- [Modo nativo](./.doc/native/datalayer.md)

---

[phpmx](https://github.com/php-mx) | [phpmx-core](https://github.com/php-mx/phpmx-core) | [phpmx-server](https://github.com/php-mx/phpmx-server) | [phpmx-datalayer](https://github.com/php-mx/phpmx-datalayer) | [phpmx-view](https://github.com/php-mx/phpmx-view)
