<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;

interface NamerInterface
{
    /**
     * Returns unique name for route
     */
    public function getName(RouteModel $route): string;
}
