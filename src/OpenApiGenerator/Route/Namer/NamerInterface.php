<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

interface NamerInterface
{
    /**
     * Returns unique name for route
     */
    public function getName(OpenApiRoute $route): string;
}
