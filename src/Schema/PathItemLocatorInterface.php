<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\spec\PathItem;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
interface PathItemLocatorInterface
{
    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $baseName, PathItem $pathItem): array;
}
