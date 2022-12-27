<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Generator;

use Kynx\Mezzio\OpenApi\OpenApiSchema;

#[OpenApiSchema('/components/schemas/EnumSimple')]
enum EnumSimple: string
{
    case First = 'first';
    case Second = 'second';
}
