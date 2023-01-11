<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiModel;

#[OpenApiModel('/components/schemas/InterfaceSimple')]
interface InterfaceSimple
{
    public function getFoo(): string|null;
}
