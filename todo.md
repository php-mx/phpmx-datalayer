Conexão com banco de dados

- Rodar migration
- LOCK e UNLOCK em migrations
- SEED
  - Inserir registros
  - Gravar os IDS dos registros com base em um nome (seed-[seedName]-[tableName]-[pos])
  - Remover registro inseridos no seed
- Deve ser capaz de se ajustar a bancos existentes
  - db.scan (rastreia o banco de dados gerando um mapa)
  - db.incorp (aplica as alterações no banco de dados)
- Deve ser capaz de gerenciar bancos de diferentes tipos via driver
  - mysql
  - sqlite
  - json
- O cache do banco deve ser desativado quando em modo TERMINAL
