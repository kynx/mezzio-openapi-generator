<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;

interface OperationWriterInterface
{
    public function write(OperationCollection $operations, HydratorCollection $hydratorCollection): void;
}
