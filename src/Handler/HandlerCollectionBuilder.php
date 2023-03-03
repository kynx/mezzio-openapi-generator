<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;

use function array_combine;
use function array_keys;
use function array_values;
use function assert;
use function implode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerCollectionBuilder
{
    public function __construct(private readonly NamerInterface $namer)
    {
    }

    public function getHandlerCollection(RouteCollection $routes, OperationCollection $operations): HandlerCollection
    {
        $collection        = new HandlerCollection();
        $handlerOperations = $this->getHandlerOperations($operations);
        $classNames        = $this->getClassNames($routes);

        foreach ($routes as $route) {
            $pointer = $route->getJsonPointer();
            assert(isset($classNames[$pointer]));
            $className = $classNames[$pointer];
            $collection->add(new HandlerModel($pointer, $className, $handlerOperations[$pointer]));
        }

        return $collection;
    }

    /**
     * @return array<string, OperationModel>
     */
    private function getHandlerOperations(OperationCollection $operations): array
    {
        $handlerOperations = [];
        foreach ($operations as $operation) {
            $handlerOperations[$operation->getJsonPointer()] = $operation;
        }

        return $handlerOperations;
    }

    /**
     * @return array<string, string>
     */
    private function getClassNames(RouteCollection $routes): array
    {
        $names = [];
        foreach ($routes as $route) {
            $names[$route->getJsonPointer()] = $this->getName($route);
        }

        return array_combine(
            array_keys($names),
            array_keys($this->namer->keyByUniqueName(array_values($names)))
        );
    }

    private function getName(RouteModel $route): string
    {
        $parts = RouteUtil::getPathParts($route->getPath());
        return implode(' ', $parts) . ' ' . $route->getMethod() . 'Handler';
    }
}
