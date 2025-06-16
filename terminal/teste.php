<?php

use Model\DbMain\DbMain;
use PhpMx\Terminal;

return new class extends Terminal {

    function __invoke()
    {
        $scheme = DbMain::teste()->_schemeAll();
        dd($scheme);
    }
};
