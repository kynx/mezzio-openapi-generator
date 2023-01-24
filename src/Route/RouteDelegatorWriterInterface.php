<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;

interface RouteDelegatorWriterInterface
{
    public function getDelegatorClassName(): string;

    public function write(RouteCollection $routes, HandlerCollection $handlerCollection): void;
}
