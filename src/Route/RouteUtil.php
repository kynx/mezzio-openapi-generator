<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use function array_map;
use function array_slice;
use function explode;
use function implode;
use function preg_replace;
use function rawurlencode;
use function str_replace;
use function trim;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\UtilTest
 */
final class RouteUtil
{
    private function __construct()
    {
    }

    /**
     * Return path with segments urlencoded but parameter placeholders preserved
     */
    public static function encodePath(string $path): string
    {
        return implode('/', array_map(function (string $segment): string {
            return str_replace(['%7B', '%7D'], ['{', '}'], rawurlencode($segment));
        }, explode('/', trim($path))));
    }

    /**
     * Returns array of path segments with parameter placeholder markers removed
     *
     * @return list<string>
     */
    public static function getPathParts(string $path): array
    {
        $parts = array_slice(explode('/', $path), 1);
        return array_map(
            fn (string $part): string => preg_replace('/\{(.*)}/Uu', '$1', $part),
            $parts
        );
    }
}
