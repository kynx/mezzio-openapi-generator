<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Handler\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\OpenApiParserTest
 */
final class OpenApiParser
{
    public function __construct(private readonly OpenApi $openApi, private readonly NamerInterface $namer)
    {
    }

    public function getHandlerCollection(): HandlerCollection
    {
        $collection = new HandlerCollection();

        foreach ($this->getHandlerClasses() as $handlerClass) {
            $collection->add($handlerClass);
        }

        return $collection;
    }

    /**
     * @return list<HandlerClass>
     */
    private function getHandlerClasses(): array
    {
        $routes = [];
        /** @var string $path */
        foreach ($this->openApi->paths as $path => $spec) {
            /** @var string $method */
            foreach ($spec->getOperations() as $method => $operation) {
                $routes[] = new OpenApiRoute($path, $method, $operation);
            }
        }

        $handlerClasses = [];
        foreach ($this->namer->keyByUniqueName($routes) as $className => $route) {
            $handlerClasses[] = new HandlerClass($className, $route);
        }

        return $handlerClasses;
    }
}
