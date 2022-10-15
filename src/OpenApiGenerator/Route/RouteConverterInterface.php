<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;

interface RouteConverterInterface
{
    /**
     * Returns sorted collection
     */
    public function sort(HandlerCollection $collection): HandlerCollection;

    /**
     * Returns path string to pass to `$router->route()`
     */
    public function convert(OpenApiOperation $operation): string;
}
