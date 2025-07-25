<?php

use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke()
    {
        if (File::check('install'))
            throw new Exception("Install already exists in project");

        $template = Path::seekForFile('library/template/terminal/install.txt');
        $template = Import::content($template);

        File::create('install', $template);

        self::echo('Install file created successfully');
    }
};
