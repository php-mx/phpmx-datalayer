<?php

use PhpMx\Log;

class_exists(Log::class);

$composerLoader = spl_autoload_functions()[0];

foreach (spl_autoload_functions() as $loader) spl_autoload_unregister($loader);

spl_autoload_register(fn($class) => Log::add('autoload', $class, fn() => $composerLoader($class)));
