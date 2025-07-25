<?php

if (!function_exists('uuid')) {

    /** Gera uma string de id unica */
    function uuid(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $id = '_';
        for ($i = 0; $i < 12; $i++)
            $id .= $characters[random_int(0, strlen($characters) - 1)];
        $id .= base_convert(time(), 10, 36);
        return $id;
    }
}
