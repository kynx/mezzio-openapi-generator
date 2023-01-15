<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use function array_pop;
use function array_slice;
use function explode;
use function implode;
use function ltrim;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\GeneratorUtilTest
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class GeneratorUtil
{
    private function __construct()
    {
    }

    public static function getNamespace(string $classString): string
    {
        $namespace = implode('\\', array_slice(explode('\\', $classString), 0, -1));
        return ltrim($namespace, '\\');
    }

    public static function getClassName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return array_pop($parts);
    }
}
