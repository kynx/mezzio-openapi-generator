<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\PathItem;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathItemLocatorInterface;

use function array_merge;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PathItemLocator implements PathItemLocatorInterface
{
    public function __construct(private readonly OperationLocator $operationLocator = new OperationLocator())
    {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $baseName, PathItem $pathItem): array
    {
        $models     = [];
        $operations = $pathItem->getOperations();

        foreach ($operations as $method => $operation) {
            $name   = $baseName . ' ' . $method;
            $models = array_merge($models, $this->operationLocator->getNamedSpecifications($name, $operation));
        }

        return $models;
    }
}
