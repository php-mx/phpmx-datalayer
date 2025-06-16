<?php

namespace PhpMx\Datalayer;

use Error;
use PhpMx\Datalayer;
use PhpMx\Dir;
use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

trait MigrationTerminalTrait
{
    protected static $dbName;
    protected static $path;

    static function up($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executeNext();

        if (!$result)
            Terminal::echo('All changes have been applied');

        return $result;
    }

    static function down($dbName = null)
    {
        self::loadDatalayer($dbName);

        $result = self::executePrev();

        if (!$result)
            Terminal::echo('All changes have been reverted');

        return $result;
    }

    protected static function loadDatalayer($dbName)
    {
        $dbName = $dbName ?? 'main';

        Datalayer::get($dbName);
        self::$dbName = Datalayer::internalName($dbName);
        self::$path = path('migration', self::$dbName);
    }

    /** Retorna a lista de arquivos de migration */
    protected static function getFiles(): array
    {
        $files = [];

        $paths = Path::seekDirs(self::$path);

        foreach ($paths as $path) {
            foreach (Dir::seekForFile($path, true) as $file)
                if (substr($file, -4) == '.php') {
                    $fileName = File::getName($file);
                    $files[substr($fileName, 0, 17)] = path($path, $file);
                }
        }

        ksort($files);

        return $files;
    }

    /** Retorna/Altera o ID da ultima migration executada */
    protected static function lastId(?int $id = null): int
    {
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfig('__migration');

        $executed = is_json($executed) ? json_decode($executed, true) : [];

        if (!is_null($id)) {
            if ($id > 0) {
                $executed[] = $id;
            } else {
                $executed = array_slice($executed, 0, $id);
            }
        }

        $datalayer->setConfig('__migration', json_encode($executed));

        return array_pop($executed) ?? 0;
    }

    /** Retorna array com todos os IDs aplicados */
    protected static function getAppliedMigrations(): array
    {
        $datalayer = Datalayer::get(self::$dbName);
        $executed = $datalayer->getConfig('__migration');

        return is_json($executed) ? json_decode($executed, true) : [];
    }

    /** Executa um arquivo de migration */
    protected static function executeMigration(string $file, bool $mode)
    {
        $logAction = $mode ? 'up' : 'down';
        $logDdName = Datalayer::externalName(self::$dbName, 'db');

        log_add("migration.$logAction", '[#] [[#]]', [$logDdName, $file,], function () use ($file, $mode) {
            Terminal::echo("run [#action] [#file]", [
                'action' => $mode ? 'up' : 'down',
                'file' => $file,
            ]);

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
        $lasId = self::lastId();

        if ($lasId) {
            $files = self::getFiles();

            if (isset($files[$lasId])) {
                self::executeMigration($files[$lasId], false);
                self::lastId(-1);
                return true;
            } else {
                throw new Error("Migration file [$lasId] not found");
            }
        }

        return  false;
    }
}
