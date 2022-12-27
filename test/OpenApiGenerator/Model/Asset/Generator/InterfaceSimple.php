<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Generator;

use Kynx\Mezzio\OpenApi\OpenApiSchema;

#[OpenApiSchema('/components/schemas/InterfaceSimple')]
interface InterfaceSimple
{
    public function getFoo(): string|null;
}
