<?php

use PhpMx\Datalayer;
use PhpMx\File;
use PhpMx\Terminal;
use PhpMx\Trait\TerminalMigrationTrait;

/**
 * Lista a situação das migrations de uma conexão com banco de dados.
 * @param string $dbName Nome do banco de dados alvo (opcional, usa 'main' por padrão).
 */
return new class {

    use TerminalMigrationTrait;

    function __invoke($dbName = 'main')
    {
        self::loadDatalayer($dbName);
        $files = self::getFiles();

        if (!empty($files)) {
            $executeds = Datalayer::get($dbName)->getConfigGroup('migration');

            Terminal::echol('[#c:sb,#]', strToPascalCase('Db ' . Datalayer::externalName($dbName)));

            foreach ($files as $id => $file) {
                $name = substr(File::getName($file), strlen($id) + 1);

                $executed = isset($executeds[$id]);
                $locked = boolval($executeds[$id]['lock'] ?? false) ? "[locked]" : '';

                $color = $executed ? 's' : 'd';
                if ($locked) $color .= 'd';

                Terminal::echol();
                Terminal::echo(" - [#c:$color,$name] [#c:sd,$file]");
                if ($locked)
                    Terminal::echo(" [#c:wd,#]", $locked);
                Terminal::echol();
            }
        } else {
            Terminal::echol('[#c:dd,- empty -]');
        }
    }
};
