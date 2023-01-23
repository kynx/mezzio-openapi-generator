<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Laminas\Filter\Word\CamelCaseToUnderscore;

use function array_map;
use function array_merge;
use function array_slice;
use function assert;
use function explode;
use function implode;
use function is_string;
use function preg_replace;
use function strtolower;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamerTest
 */
final class DotSnakeCaseNamer implements NamerInterface
{
    private CamelCaseToUnderscore $filter;

    public function __construct(private string $prefix)
    {
        $this->filter = new CamelCaseToUnderscore();
    }

    public function getName(RouteModel $route): string
    {
        $parts = [$this->prefix];

        $routeParts = array_map(
            fn (string $part): string => preg_replace('/{(.+)}/', '$1', $part),
            array_slice(explode('/', $route->getPath()), 1)
        );
        $parts      = array_merge($parts, $routeParts);
        $parts[]    = $route->getMethod();

        return implode(
            '.',
            array_map(fn (string $part): string => strtolower($this->filter($part)), $parts)
        );
    }

    private function filter(string $part): string
    {
        $filtered = $this->filter->filter($part);
        assert(is_string($filtered));
        return $filtered;
    }
}
