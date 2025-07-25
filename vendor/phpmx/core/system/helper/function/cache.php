<?php

use PhpMx\Json;
use PhpMx\Log;

if (!function_exists('cache')) {

    /** Armazena e recupera o retorno de uma Closure em /library/cache */
    function cache(string $cacheName, Closure $action): mixed
    {
        $cacheName = strToCamelCase($cacheName);
        return Log::add('cache', $cacheName, function () use ($cacheName, $action) {
            $file = path('library/cache', $cacheName);

            if (!env('USE_CACHE_FILE'))
                return $action();

            $result = Json::import($file);

            if (!env('DEV') && !empty($result))
                return array_shift($result);

            try {
                $result = $action();
            } catch (Throwable $e) {
                throw $e;
            }

            if (is_closure($result))
                return $result;

            try {
                Json::export($file, [$result]);
            } catch (Throwable) {
            }

            return $result;
        });
    }
}
