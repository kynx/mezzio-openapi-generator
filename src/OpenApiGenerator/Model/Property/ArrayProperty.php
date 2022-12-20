<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\ArrayPropertyTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ArrayProperty extends AbstractProperty
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $originalName,
        protected readonly PropertyMetadata $metadata,
        private readonly bool $isList,
        private readonly PropertyType|string $memberType
    ) {
    }

    public function isList(): bool
    {
        return $this->isList;
    }

    public function getMemberType(): PropertyType|string
    {
        return $this->memberType;
    }
}
