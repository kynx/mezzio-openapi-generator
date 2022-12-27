<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Generator;

use Kynx\Mezzio\OpenApi\OpenApiSchema;

#[OpenApiSchema('/components/schemas/ClassSimple')]
final class ClassSimple
{
    public function __construct(private readonly string|null $foo = null)
    {
    }


    public function getFoo(): string|null
    {
        return $this->foo;
    }
}
