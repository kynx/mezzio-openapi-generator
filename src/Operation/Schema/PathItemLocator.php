<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Schema;

use cebe\openapi\spec\PathItem;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathItemLocatorInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PathItemLocator implements PathItemLocatorInterface
{
    /**
     * @inheritDoc
     */
    public function getNamedSpecifications(string $baseName, PathItem $pathItem): array
    {
        $models     = [];
        $operations = $pathItem->getOperations();

        foreach ($operations as $method => $operation) {
            $name             = $baseName . ' ' . $method;
            $pointer          = $operation->getDocumentPosition()?->getPointer() ?? '';
            $models[$pointer] = new NamedSpecification($name . ' Operation', $operation);
        }

        return $models;
    }
}
