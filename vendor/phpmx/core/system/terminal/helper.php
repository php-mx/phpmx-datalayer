<?php

use PhpMx\Dir;
use PhpMx\Import;
use PhpMx\Path;
use PhpMx\Terminal;

return new class extends Terminal {

    protected $used = [];

    function __invoke($command = null)
    {
        foreach (Path::seekForDirs('system/terminal') as $path) {
            $origin = $this->getOrigim($path);

            self::echo();
            self::echo('[[#]]', $origin);
            self::echoLine();

            foreach ($this->getCommandsIn($path, $origin) as $cmd) {
                if (is_null($command) || str_starts_with($cmd['terminal'], $command)) {
                    self::echo(' [#terminal] ([#file])[#status]', $cmd);
                    foreach ($cmd['variations'] as $variation)
                        self::echo('   php mx [#][#]', [$cmd['terminal'], $variation]);
                    self::echo();
                }
            };
        }
    }

    protected function getOrigim($path)
    {
        if ($path === 'system/terminal') return 'CURRENT-PROJECT';

        if (str_starts_with($path, 'vendor/')) {
            $parts = explode('/', $path);
            return $parts[1] . '-' . $parts[2];
        }

        return 'unknown';
    }

    protected function getCommandsIn($path, $origin)
    {
        $commands = [];
        foreach (Dir::seekForFile($path, true) as $ref) {
            $terminal = path($ref);
            $terminal = substr($ref, 0, -4);
            $terminal = str_replace('/', '.', $terminal);

            $file = path($path, $ref);

            $variations = [''];

            $this->used[$terminal] = $this->used[$terminal] ?? $origin;

            try {
                $command = Import::return($file);
                $invoke = new ReflectionMethod($command, '__invoke');
                foreach ($invoke->getParameters() as $param) {
                    $name = '<' . $param->getName() . '>';
                    if (!$param->isOptional()) {
                        $variations[0] .= " $name";
                    } else {
                        $variations[] = end($variations) . " $name";
                    }
                }
            } catch (Throwable) {
                $variations = [' <???>'];
            }

            $commands[$terminal] = [
                'terminal' => $terminal,
                'file' => $file,
                'variations' => $variations,
                'status' => $this->used[$terminal] == $origin ? '' : ' [replaced in ' . $this->used[$terminal] . ']'
            ];
        }
        ksort($commands);
        return $commands;
    }
};
