<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Converter;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;

interface ConverterInterface
{
    /**
     * Returns sorted collection
     */
    public function sort(RouteCollection $collection): RouteCollection;

    /**
     * Returns path string to pass to `$router->route()`
     */
    public function convert(RouteModel $route): string;
}
