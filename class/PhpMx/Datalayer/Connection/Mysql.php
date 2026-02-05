<?php

namespace PhpMx\Datalayer\Connection;

use Exception;
use PDO;
use PhpMx\Cif;
use PhpMx\Datalayer;
use PhpMx\Datalayer\Query;
use PhpMx\Log;

/** Driver de conexão Mysql. */
class Mysql extends BaseConnection
{
    protected string $pdoDriver = 'pdo_mysql';

    /** Inicializa a conexão */
    protected function load()
    {
        $envName = strtoupper($this->dbName);

        $this->data['host'] = $this->data['host'] ?? env("DB_{$envName}_HOST");
        $this->data['data'] = $this->data['data'] ?? env("DB_{$envName}_DATA");
        $this->data['user'] = $this->data['user'] ?? env("DB_{$envName}_USER");
        $this->data['pass'] = $this->data['pass'] ?? env("DB_{$envName}_PASS");
        $this->data['port'] = $this->data['port'] ?? env("DB_{$envName}_PORT");

        if (empty($this->data['port'])) unset($this->data['port']);

        $this->data['pass'] = Cif::off($this->data['pass']);

        $dsn = "mysql:host={$this->data['host']}";

        if ($this->data['port']) $dsn .= ";port={$this->data['port']}";

        $dsn .= ";dbname={$this->data['data']};charset=utf8";

        $this->instancePDO = [
            $dsn,
            $this->data['user'],
            $this->data['pass']
        ];
    }

    /** Retorna a instancia PDO da conexão */
    protected function &pdo(): PDO
    {
        if (is_array($this->instancePDO)) {
            Log::add('datalayer.start', prepare('[#] mysql', Datalayer::externalName($this->dbName, 'Db')), function () {
                $this->instancePDO = new PDO(...$this->instancePDO);
            });
        }
        return $this->instancePDO;
    }

    /** Carrega as configurações do banco armazenadas na tabela __config */
    protected function initConfig(): void
    {
        if (!$this->configInitialized) {

            $configTableExistsQuery = Query::select('INFORMATION_SCHEMA.TABLES')
                ->where('table_schema', $this->data['data'])
                ->where('table_name', '__config')
                ->limit(1);

            if (!count($this->executeQuery($configTableExistsQuery)))
                $this->executeQuery(
                    'CREATE TABLE __config (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        `group` VARCHAR(100) NOT NULL,
                        `name` VARCHAR(100) NOT NULL,
                        `value` LONGTEXT NOT NULL,
                        UNIQUE KEY `config_unique_path` (`group`, `name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
                );

            $this->configInitialized = true;
        }
    }

    /** Query para criação de tabelas */
    protected function schemeQueryCreateTable(string $tableName, ?string $comment, array $fields): array
    {
        $queryFields = ['`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'];

        foreach ($fields['add'] ?? [] as $fielName => $field)
            if ($field)
                $queryFields[] = $this->schemeTemplateField($fielName, $field);

        return [
            prepare("CREATE TABLE `[#name]` ([#fields]) DEFAULT CHARSET=utf8[#comment] ENGINE=InnoDB;", [
                'name' => $tableName,
                'fields' => implode(', ', $queryFields),
                'comment' => $comment ? " COMMENT='$comment'" : ''
            ])
        ];
    }

    /** Query para alteração de tabelas */
    protected function schemeQueryAlterTable(string $tableName, ?string $comment, array $fields): array
    {
        $query = [];

        if (!is_null($comment)) {
            $query[] = prepare("ALTER TABLE `[#table]` COMMENT='[#comment]'", [
                'table' => $tableName,
                'comment' => $comment
            ]);
        }

        foreach ($fields['add'] as $fieldName => $fieldData) {
            $query[] = prepare('ALTER TABLE `[#table]` ADD COLUMN [#fieldQuery]', [
                'table' => $tableName,
                'fieldQuery' => $this->schemeTemplateField($fieldName, $fieldData)
            ]);
        }

        foreach ($fields['drop'] as $fieldName => $fieldData) {
            $query[] = prepare('ALTER TABLE `[#table]` DROP COLUMN `[#fieldName]`', [
                'table' => $tableName,
                'fieldName' => $fieldName
            ]);
        }

        foreach ($fields['alter'] as $fieldName => $fieldData) {
            $query[] = prepare('ALTER TABLE `[#table]` MODIFY COLUMN [#fieldQuery]', [
                'table' => $tableName,
                'fieldQuery' => $this->schemeTemplateField($fieldName, $fieldData)
            ]);
        }

        return $query;
    }

    /** Query para remoção de tabelas */
    protected function schemeQueryDropTable(string $tableName): array
    {
        return ["DROP TABLE `$tableName`"];
    }

    /** Query para atualização de index */
    protected function schemeQueryUpdateTableIndex(string $name, array $index): array
    {
        $query = [];

        foreach ($index as $indexName => $scheme) {
            $quotedIndex = "`{$name}_{$indexName}`";
            if ($scheme) {
                list($field, $unique) = $scheme;
                if ($unique) {
                    $query[] = "CREATE UNIQUE INDEX $quotedIndex ON `$name`(`$field`);";
                } else {
                    $query[] = "CREATE INDEX $quotedIndex ON `$name`(`$field`);";
                }
            } else {
                $query[] = "DROP INDEX $quotedIndex ON `$name`;";
            }
        }

        return $query;
    }

    /** Retorna o template do campo para composição de querys */
    protected static function schemeTemplateField(string $fieldName, array $field): string
    {
        $prepare = '';
        $field['name'] = $fieldName;
        $field['null'] = $field['null'] ? '' : ' NOT NULL';
        $field['comment'] = isset($field['comment']) && $field['comment'] !== '' ? " COMMENT '{$field['comment']}'" : '';

        switch ($field['type']) {
            case 'idx':
            case 'time':
            case 'int':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "`[#name]` int([#size])[#default][#null][#comment]";
                break;

            case 'boolean':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "`[#name]` tinyint([#size])[#default][#null][#comment]";
                break;

            case 'float':
                $field['default'] = is_null($field['default']) ? '' : ' DEFAULT ' . $field['default'];
                $prepare = "`[#name]` float([#size])[#default][#null][#comment]";
                break;

            case 'text':
            case 'json':
                $field['default'] = '';
                $prepare = "`[#name]` text[#null][#comment]";
                break;

            case 'string':
            case 'email':
            case 'md5':
            case 'mx5':
                $field['default'] = is_null($field['default']) ? '' : " DEFAULT '" . $field['default'] . "'";
                $prepare = "`[#name]` varchar([#size])[#default][#null][#comment]";
                break;

            default:
                throw new Exception("Type [$field[type]] not suported");
        }

        return prepare($prepare, $field);
    }
}
