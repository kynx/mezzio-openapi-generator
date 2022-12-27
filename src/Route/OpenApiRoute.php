<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\spec\Operation;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\OpenApiRouteTest
 */
final class OpenApiRoute
{
    public function __construct(private string $path, private string $method, private Operation $operation)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }
}
