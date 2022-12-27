<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerClassTest
 *
 * @psalm-immutable
 */
final class HandlerClass
{
    /**
     * @param class-string $className
     */
    public function __construct(private readonly string $className, private readonly OpenApiRoute $route)
    {
    }

    /**
     * Returns true if path and method match given operation
     */
    public function matches(HandlerClass $handlerClass): bool
    {
        return $this->route->getPath() === $handlerClass->route->getPath()
            && $this->route->getMethod() === $handlerClass->route->getMethod();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getRoute(): OpenApiRoute
    {
        return $this->route;
    }
}
