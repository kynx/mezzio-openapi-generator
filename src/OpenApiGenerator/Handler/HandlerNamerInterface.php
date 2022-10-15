<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;

interface HandlerNamerInterface
{
    /**
     * @param list<OpenApiOperation> $operations
     * @return array<string, OpenApiOperation>
     */
    public function keyByUniqueName(array $operations): array;
}
