<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\UnionPropertyTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class UnionProperty extends AbstractProperty
{
    private array $members;

    public function __construct(
        protected readonly string $name,
        protected readonly string $originalName,
        protected readonly PropertyMetadata $metadata,
        PropertyType|string ...$members
    ) {
        $this->members = $members;
    }

    public function getMembers(): array
    {
        return $this->members;
    }
}
