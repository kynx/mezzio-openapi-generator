<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Laminas\Filter\Word\CamelCaseToUnderscore;

use function array_slice;
use function explode;
use function strtolower;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\DotSnakeCaseNamerTest
 */
final class DotSnakeCaseNamer implements RouteNamerInterface
{
    private string $prefix;
    private CamelCaseToUnderscore $filter;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
        $this->filter = new CamelCaseToUnderscore();
    }

    public function getName(OpenApiOperation $operation): string
    {
        $parts = [$this->prefix];

        if ($operation->getOperationId() !== null) {
            $parts[] = $operation->getOperationId();
        } else {
            $routeParts = array_map(
                fn (string $part): string => preg_replace('/{(.+)}/', '$1', $part),
                array_slice(explode('/', $operation->getPath()), 1)
            );
            $parts = array_merge($parts, $routeParts);
            $parts[] = $operation->getMethod();
        }

        return implode(
            '.',
            array_map(fn (string $part): string => strtolower($this->filter->filter($part)), $parts)
        );
    }
}