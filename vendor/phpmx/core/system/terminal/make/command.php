<?php

use PhpMx\File;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke(string $command)
    {
        $command = remove_accents($command);
        $command = strtolower($command);

        $commandFile = explode('.', $command);
        $commandFile = array_map(fn($v) => strtolower($v), $commandFile);
        $commandFile = path('system/terminal', ...$commandFile);
        $commandFile = File::setEx($commandFile, 'php');

        if (File::check($commandFile))
            throw new Exception("Command [$command] already exists in project");

        $template = Path::seekForFile('library/template/terminal/command.txt');
        $template = Import::content($template, ['command' => $command]);

        File::create($commandFile, $template);

        self::echo('Command [[#]] created successfully', $command);
    }
};
