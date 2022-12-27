<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler\Namer;

use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

interface NamerInterface
{
    /**
     * @param list<OpenApiRoute> $routes
     * @return array<class-string, OpenApiRoute>
     */
    public function keyByUniqueName(array $routes): array;
}
