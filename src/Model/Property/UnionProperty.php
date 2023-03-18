<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;

use function array_filter;
use function array_values;
use function implode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\UnionPropertyTest
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class UnionProperty extends AbstractProperty
{
    /** @var list<PropertyType|ClassString> */
    private array $types;

    public function __construct(
        protected readonly string $name,
        protected readonly string $originalName,
        protected readonly PropertyMetadata $metadata,
        private readonly PropertyList|PropertyValue|null $discriminator,
        PropertyType|ClassString ...$types
    ) {
        $this->types = array_values($types);
    }

    public function getDiscriminator(): PropertyList|PropertyValue|null
    {
        return $this->discriminator;
    }

    public function getPhpType(): string
    {
        $types = [];
        foreach ($this->types as $type) {
            $types[] = $this->getTypeString($type);
        }
        return implode('|', $types);
    }

    public function getUses(): array
    {
        $uses = [];
        foreach ($this->types as $type) {
            $uses[] = $this->getClassString($type);
        }
        return array_filter($uses);
    }

    public function getDocBlockType(bool $forUnion = false): string|null
    {
        if (! $forUnion) {
            return null;
        }
        $types = [];
        foreach ($this->types as $type) {
            $types[] = $this->getShortType($type);
        }
        return implode('|', $types);
    }

    /**
     * @return list<PropertyType|ClassString>
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
