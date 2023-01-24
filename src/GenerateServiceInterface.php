<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;

interface GenerateServiceInterface
{
    public function getModels(OpenApi $openApi): ModelCollection;

    public function getOperations(OpenApi $openApi, ModelCollection $modelCollection): OperationCollection;

    public function getRoutes(OpenApi $openApi): RouteCollection;

    public function getHandlers(RouteCollection $routes, OperationCollection $operations): HandlerCollection;

    public function createModels(ModelCollection $collection): void;

    public function createHydrators(HydratorCollection $collection): void;

    public function createOperations(OperationCollection $collection, HydratorCollection $hydratorCollection): void;

    public function createHandlers(HandlerCollection $collection): void;
}
