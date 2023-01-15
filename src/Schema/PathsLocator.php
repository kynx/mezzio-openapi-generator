<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;

use function array_merge;
use function implode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\PathsLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PathsLocator
{
    public function __construct(private readonly PathItemLocatorInterface $pathItemLocator)
    {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(Paths $paths): array
    {
        $models = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            /** @psalm-suppress DocblockTypeContradiction Upstream typehint is wrong */
            if ($pathItem === null) {
                continue;
            }

            $name   = implode(' ', RouteUtil::getPathParts((string) $path));
            $models = array_merge($models, $this->pathItemLocator->getNamedSpecifications($name, $pathItem));
        }

        return $models;
    }
}
