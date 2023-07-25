<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelResolver;

interface GenerateServiceInterface
{
    public function getModels(OpenApi $openApi): ModelCollection;

    public function getOperations(OpenApi $openApi, ModelCollection $modelCollection): OperationCollection;

    public function getRoutes(OpenApi $openApi, SecurityModelResolver $securityModelResolver): RouteCollection;

    public function getHandlers(RouteCollection $routes, OperationCollection $operations): HandlerCollection;

    public function createModels(ModelCollection $collection): void;

    public function createHydrators(HydratorCollection $collection): void;

    public function createOperations(OperationCollection $collection, HydratorCollection $hydratorCollection): void;

    public function createRouteDelegator(RouteCollection $routes, HandlerCollection $handlers): void;

    public function createHandlers(HandlerCollection $collection): void;

    public function createConfigProvider(OperationCollection $operations, HandlerCollection $handlers): void;
}
