<?php

namespace PhpMx\Trait;

use Error;
use PhpMx\Datalayer;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Import;
use PhpMx\Log;
use PhpMx\Terminal;

/** Terminal trait para aplicar e reverter migrations de banco via datalayer. */
trait TerminalMigrationTrait
{
    protected static $dbName;
    protected static $path;

    static function up($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executeNext();

        if (!$result)
            Terminal::echol('[#c:s,All changes have been applied]');

        return $result;
    }

    static function down($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executePrev();

        if (!$result)
            Terminal::echol('[#c:s,All changes have been reverted]');

        return $result;
    }

    static function lock($dbName = null)
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

    static function unlock($dbName = null)
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

    protected static function loadDatalayer($dbName)
    {
        Datalayer::get($dbName);
        self::$dbName = Datalayer::internalName($dbName);
        self::$path = path('system/datalayer', self::$dbName, 'migration');
    }

    /** Retorna a lista de arquivos de migration */
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

    /** Retorna/Altera o ID da ultima migration executada */
    protected static function lastId(?string $id = null): string
    {
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfigGroup('migration');

        if (!is_null($id)) {
            if ($id != "-1") {
                $executed[$id] = ['lock' => 0];
            } else {
                $lastId = array_key_last($executed);

                // Se estiver travado, apenas avisamos e NÃO removemos do banco
                if ($lastId && ($executed[$lastId]['lock'] ?? 0) > 0) {
                    // Retornamos o ID para o executePrev saber quem ignorar
                    return $lastId;
                }

                unset($executed[$lastId]);
            }
            $datalayer->setConfigGroup('migration', $executed);
        }

        $keys = array_keys($executed);
        return (string) (array_pop($keys) ?? '');
    }

    /** Retorna array com todos os IDs aplicados */
    protected static function getAppliedMigrations(): array
    {
        $datalayer = Datalayer::get(self::$dbName);
        $data = $datalayer->getConfigGroup('migration');
        return array_keys($data);
    }

    /** Executa um arquivo de migration */
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

    /** Reverte o ultimo arquivo executado da lista de migration */
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
