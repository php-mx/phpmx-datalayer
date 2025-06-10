<?php

namespace Controller;

use Model\DbMain\DbMain;

class Teste
{
    function default()
    {
        DbMain::access(4)->_save();
        return STS_OK;
    }
}
