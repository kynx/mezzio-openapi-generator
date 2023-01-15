<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

interface HydratorWriterInterface
{
    public function write(HydratorCollection $hydratorCollection): void;
}
