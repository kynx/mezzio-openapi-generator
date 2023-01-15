<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\SpecBaseObject;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Schema\NamedSpecificationTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class NamedSpecification
{
    public function __construct(private readonly string $name, private readonly SpecBaseObject $specification)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpecification(): SpecBaseObject
    {
        return $this->specification;
    }

    public function getJsonPointer(): string
    {
        return $this->specification->getDocumentPosition()?->getPointer() ?? '';
    }
}
