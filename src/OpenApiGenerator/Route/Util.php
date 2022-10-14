<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use function explode;
use function implode;
use function rawurlencode;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\UtilTest
 */
final class Util
{
    /**
     * Return path with segments urlencoded but parameter placeholders preserved
     */
    public static function encodePath(string $path): string
    {
        return implode('/', array_map(function (string $segment): string {
            return str_replace(['%7B', '%7D'], ['{', '}'], rawurlencode($segment));
        }, explode('/', trim($path))));
    }
}