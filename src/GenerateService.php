<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;

final class GenerateService implements GenerateServiceInterface
{
    public function __construct(
        private readonly OpenApiLocator $modelLocator,
        private readonly OpenApiLocator $operationLocator,
        private readonly ModelCollectionBuilder $modelCollectionBuilder,
        private readonly OperationCollectionBuilder $operationCollectionBuilder,
        private readonly RouteCollectionBuilder $routeCollectionBuilder,
        private readonly HandlerCollectionBuilder $handlerCollectionBuilder,
        private readonly ModelWriterInterface $modelWriter,
        private readonly HydratorWriterInterface $hydratorWriter,
        private readonly OperationWriterInterface $operationWriter,
        private readonly HandlerWriterInterface $handlerWriter
    ) {
    }

    public function getModels(OpenApi $openApi): ModelCollection
    {
        $namedSpecifications = $this->modelLocator->getNamedSpecifications($openApi);
        return $this->modelCollectionBuilder->getModelCollection($namedSpecifications);
    }

    public function getOperations(OpenApi $openApi, ModelCollection $modelCollection): OperationCollection
    {
        $namedSpecifications = $this->operationLocator->getNamedSpecifications($openApi);
        $classMap            = $modelCollection->getClassMap();
        return $this->operationCollectionBuilder->getOperationCollection($namedSpecifications, $classMap);
    }

    public function getRoutes(OpenApi $openApi): RouteCollection
    {
        return $this->routeCollectionBuilder->getRouteCollection($openApi);
    }

    public function getHandlers(RouteCollection $routes, OperationCollection $operations): HandlerCollection
    {
        return $this->handlerCollectionBuilder->getHandlerCollection($routes, $operations);
    }

    public function createModels(ModelCollection $collection): void
    {
        $this->modelWriter->write($collection);
    }

    public function createHydrators(HydratorCollection $collection): void
    {
        $this->hydratorWriter->write($collection);
    }

    public function createOperations(OperationCollection $collection, HydratorCollection $hydratorCollection): void
    {
        $this->operationWriter->write($collection, $hydratorCollection);
    }

    public function createHandlers(HandlerCollection $collection): void
    {
        $this->handlerWriter->write($collection);
    }
}
