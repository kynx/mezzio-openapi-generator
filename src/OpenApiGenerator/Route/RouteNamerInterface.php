<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;

interface RouteNamerInterface
{
    /**
     * Returns unique name for route
     */
    public function getName(OpenApiOperation $operation): string;
}
