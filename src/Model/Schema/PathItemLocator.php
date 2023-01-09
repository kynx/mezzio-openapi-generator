<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\PathItem;

use function array_merge;
use function count;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class PathItemLocator
{
    private OperationLocator $operationLocator;

    public function __construct()
    {
        $this->operationLocator = new OperationLocator();
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSchemas(string $baseName, PathItem $pathItem): array
    {
        $models        = [];
        $operations    = $pathItem->getOperations();
        $numOperations = count($operations);

        foreach ($operations as $method => $operation) {
            if ($numOperations > 1) {
                $name = $baseName . ' ' . $method;
            } else {
                $name = $baseName;
            }
            $models = array_merge($models, $this->operationLocator->getNamedSchemas($name, $operation));
        }

        return $models;
    }
}
