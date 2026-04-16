<?php

namespace PhpMx\Trait;

use PhpMx\Datalayer;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Import;
use PhpMx\Log;
use PhpMx\Terminal;

/** Trait com a lógica de execução de migrações usada pelos comandos de terminal (migration:up, down, lock, etc.). */
trait TerminalMigrationTrait
{
    protected static $dbName;
    protected static $path;

    /**
     * Aplica a próxima migração pendente.
     * @param string|null $dbName Nome do banco de dados.
     * @return bool True se uma migração foi aplicada, false se não havia pendentes.
     */
    protected static function up($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executeNext();

        if (!$result)
            Terminal::echol('[#c:s,All changes have been applied]');

        return $result;
    }

    /**
     * Reverte a última migração aplicada.
     * @param string|null $dbName Nome do banco de dados.
     * @return bool True se uma migração foi revertida, false se não havia aplicadas.
     */
    protected static function down($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executePrev();

        if (!$result)
            Terminal::echol('[#c:s,All changes have been reverted]');

        return $result;
    }

    /**
     * Aplica um nível de lock em todas as migrações sem lock, impedindo rollback.
     * @param string|null $dbName Nome do banco de dados.
     */
    protected static function lock($dbName = null)
    {
        self::loadDatalayer($dbName);
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfigGroup('migration');

        $maxLock = 0;
        foreach ($executed as $m) $maxLock = max($maxLock, $m['lock'] ?? 0);

        $newLock = $maxLock + 1;
        $changed = false;

        foreach ($executed as &$m)
            if (($m['lock'] ?? 0) === 0) {
                $m['lock'] = $newLock;
                $changed = true;
            }


        if ($changed) {
            $datalayer->setConfigGroup('migration', $executed);
            Terminal::echol("[#c:s,Lock level $newLock applied to all current migrations]");
        }
    }

    /**
     * Remove o nível de lock mais recente, permitindo rollback das migrações bloqueadas.
     * @param string|null $dbName Nome do banco de dados.
     */
    protected static function unlock($dbName = null)
    {
        self::loadDatalayer($dbName);
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfigGroup('migration');

        $maxLock = 0;
        foreach ($executed as $m) $maxLock = max($maxLock, $m['lock'] ?? 0);

        if ($maxLock === 0) {
            Terminal::echol("[#c:w,No locks found to release]");
            return;
        }

        foreach ($executed as &$m)
            if (($m['lock'] ?? 0) === $maxLock)
                $m['lock'] = 0;

        $datalayer->setConfigGroup('migration', $executed);
        Terminal::echol("[#c:s,Lock level $maxLock released]");
    }

    /**
     * Inicializa a conexão com o banco e define o dbName e o caminho dos arquivos de migração.
     * @param string|null $dbName Nome do banco de dados.
     */
    protected static function loadDatalayer($dbName)
    {
        Datalayer::get($dbName);
        self::$dbName = Datalayer::internalName($dbName);
        self::$path = path('system/datalayer', self::$dbName, 'migration');
    }

    /**
     * Retorna todos os arquivos de migração disponíveis ordenados por timestamp (ID).
     * @return array Array associativo [id => caminho_absoluto].
     */
    protected static function getFiles(): array
    {
        $files = [];

        foreach (Dir::seekForFile(self::$path, true) as $file)
            if (substr($file, -4) == '.php') {
                $fileName = File::getName($file);
                $files[substr($fileName, 0, 17)] = path(self::$path, $file);
            }

        ksort($files);

        return $files;
    }

    /**
     * Retorna o ID da última migração aplicada. Se $id for fornecido, registra ou remove do histórico.
     * Passar "-1" remove a última entrada; qualquer outro valor adiciona o ID ao histórico.
     * @param string|null $id ID a registrar/remover, ou null para apenas consultar.
     * @return string ID da última migração aplicada (ou string vazia se nenhuma).
     */
    protected static function lastId(?string $id = null): string
    {
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfigGroup('migration');

        if (!is_null($id)) {
            if ($id != "-1") {
                $executed[$id] = ['lock' => 0];
            } else {
                $lastId = array_key_last($executed);

                if ($lastId && ($executed[$lastId]['lock'] ?? 0) > 0) {
                    return $lastId;
                }

                unset($executed[$lastId]);
            }
            $datalayer->setConfigGroup('migration', $executed);
        }

        $keys = array_keys($executed);
        return (string) (array_pop($keys) ?? '');
    }

    /**
     * Retorna os IDs de todas as migrações já aplicadas no banco.
     * @return array Lista de IDs (timestamps) das migrações aplicadas.
     */
    protected static function getAppliedMigrations(): array
    {
        $datalayer = Datalayer::get(self::$dbName);
        $data = $datalayer->getConfigGroup('migration');
        return array_keys($data);
    }

    /**
     * Executa um arquivo de migração no modo up ou down.
     * @param string $file Caminho absoluto do arquivo de migração.
     * @param bool $mode True para up (aplicar), false para down (reverter).
     */
    protected static function executeMigration(string $file, bool $mode)
    {
        $logAction = $mode ? 'up' : 'down';
        $logDdName = Datalayer::externalName(self::$dbName, 'db');

        Log::add("migration.$logAction", "$logDdName [$file]", function () use ($file, $mode) {

            if ($mode)
                Terminal::echol("run [#c:s,up] [#c:p,#]", $file);

            if (!$mode)
                Terminal::echol("run [#c:w,down] [#c:p,#]", $file);

            $class = substr($file, 6, -4);
            $class = str_replace_all("/", "\\", $class);

            $migration = Import::return($file);
            $migration->execute(self::$dbName, $mode);
        });
    }

    /**
     * Localiza e executa a próxima migração pendente (up).
     * @return bool True se alguma migração foi executada, false se todas já foram aplicadas.
     */
    protected static function executeNext(): bool
    {
        $files = self::getFiles();
        $applied = self::getAppliedMigrations();

        foreach ($files as $id => $file) {
            if (!in_array($id, $applied)) {
                self::executeMigration($file, true);
                self::lastId($id);
                return true;
            }
        }

        return false;
    }

    /**
     * Localiza e reverte a última migração aplicada (down). Respeita o lock.
     * @return bool True se alguma migração foi revertida, false caso contrário.
     */
    protected static function executePrev()
    {
        $datalayer = Datalayer::get(self::$dbName);
        $applied = $datalayer->getConfigGroup('migration');
        $lastId = array_key_last($applied);

        if ($lastId) {

            $files = self::getFiles();

            if (($applied[$lastId]['lock'] ?? 0) > 0) {
                Terminal::echol("[#c:dd,run] [#c:wd,down] [#c:pd,#] [#c:wd,locked]", $files[$lastId]);
                return false;
            }

            if (isset($files[$lastId])) {
                self::executeMigration($files[$lastId], false);
                self::lastId("-1");
                return true;
            } else {
                Terminal::echol("[#c:e,Error:] Migration file [$lastId] not found");
                return false;
            }
        }

        return false;
    }
}
