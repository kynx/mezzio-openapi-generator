<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;

interface HandlerNamerInterface
{
    /**
     * @return class-string
     */
    public function getName(OpenApiOperation $operation): string;
}