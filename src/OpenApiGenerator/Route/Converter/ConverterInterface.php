<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Converter;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

interface ConverterInterface
{
    /**
     * Returns sorted collection
     */
    public function sort(HandlerCollection $collection): HandlerCollection;

    /**
     * Returns path string to pass to `$router->route()`
     */
    public function convert(OpenApiRoute $route): string;
}
