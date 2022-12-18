<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\PathItem;

use function array_merge;
use function count;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocatorTest
 */
final class PathItemLocator
{
    private OperationLocator $operationLocator;

    public function __construct()
    {
        $this->operationLocator = new OperationLocator();
    }

    /**
     * @return array<string, Model>
     */
    public function getModels(string $baseName, PathItem $pathItem): array
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
            $models = array_merge($models, $this->operationLocator->getModels($name, $operation));
        }

        return $models;
    }
}
