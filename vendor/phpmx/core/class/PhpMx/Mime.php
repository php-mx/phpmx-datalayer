<?php

namespace PhpMx;

abstract class Mime
{
    protected static array $MIMETYPE = [
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'php' => 'text/html',
        'php' => 'text/x-php',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'webp' => 'image/webp',

        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',

        'mp3' => 'audio/mpeg',

        'mov' => 'video/quicktime',
        'qt' => 'video/quicktime',

        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',

        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',

        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

        'eot' => 'application/vnd.ms-fontobject',
        'ttf' => 'application/octet-stream',
        'woff' => 'application/font-woff',
    ];

    /** Retorna a extensão de um mimetype */
    static function getExMime(string $mime): ?string
    {
        foreach (self::$MIMETYPE as $ex => $item)
            if (strtolower($item) == strtolower($mime))
                return strtolower($ex);

        return null;
    }

    /** Retorna o mimetype de uma extensão */
    static function getMimeEx(string $ex): ?string
    {
        return self::$MIMETYPE[strtolower($ex)] ?? null;
    }

    /** Retorna o mimetype de um arquivo */
    static function getMimeFile(string $file): ?string
    {
        if (File::check($file))
            return strtolower(mime_content_type(path($file)));

        return null;
    }

    /** Retorna verifica se uma extensão corresponde a algum mimetype fornecido */
    static function checkMimeEx(string $ex, string ...$compare): bool
    {
        $mime = self::getMimeEx($ex) ?? '';
        return $mime ? self::checkMimeMime($mime, ...$compare) : null;
    }

    /** Retorna verifica se um mimetype corresponde a algum mimetype fornecido */
    static function checkMimeMime(string $mime, string ...$compare): bool
    {
        foreach ($compare as $item) {
            $item = strpos($item, '/') ? $item : self::getMimeEx($item);
            if (strtolower($item) == strtolower($mime))
                return true;
        }

        return false;
    }

    /** Retorna verifica se um arquivo corresponde a algum mimetype fornecido */
    static function checkMimeFile(string $file, string ...$compare): bool
    {
        $mime = self::getMimeFile($file) ?? '';
        return $mime ? self::checkMimeMime($mime, ...$compare) : null;
    }
}
