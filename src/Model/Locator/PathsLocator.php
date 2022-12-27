<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;

use function array_merge;
use function implode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\PathsLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class PathsLocator
{
    private PathItemLocator $pathItemLocator;

    public function __construct()
    {
        $this->pathItemLocator = new PathItemLocator();
    }

    /**
     * @return array<string, NamedSchema>
     */
    public function getNamedSchemas(Paths $paths): array
    {
        $models = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            /** @psalm-suppress DocblockTypeContradiction Upstream typehint is wrong */
            if ($pathItem === null) {
                continue;
            }

            $name   = implode(' ', RouteUtil::getPathParts((string) $path));
            $models = array_merge($models, $this->pathItemLocator->getNamedSchemas($name, $pathItem));
        }

        return $models;
    }
}
