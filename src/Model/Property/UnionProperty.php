<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;

use function array_values;

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
    /** @var list<PropertyType|string> */
    private array $members;

    public function __construct(
        protected readonly string $name,
        protected readonly string $originalName,
        protected readonly PropertyMetadata $metadata,
        private readonly PropertyList|PropertyValue|null $discriminator,
        PropertyType|string ...$members
    ) {
        $this->members = array_values($members);
    }

    public function getDiscriminator(): PropertyList|PropertyValue|null
    {
        return $this->discriminator;
    }

    /**
     * @return list<PropertyType|string>
     */
    public function getMembers(): array
    {
        return $this->members;
    }
}
