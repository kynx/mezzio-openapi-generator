<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\ArrayPropertyTest
 *
 * @psalm-immutable
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
        private readonly PropertyType|ClassString $type
    ) {
    }

    public function isList(): bool
    {
        return $this->isList;
    }

    public function getType(): PropertyType|ClassString
    {
        return $this->type;
    }

    public function getPhpType(): string
    {
        return 'array';
    }

    public function getUses(): array
    {
        $use = $this->getClassString($this->type);
        return $use === null ? [] : [$use];
    }

    public function getDocBlockType(bool $forUnion = false): string|null
    {
        $null     = '';
        $metadata = $this->getMetadata();
        if ($metadata->isNullable() || ! $metadata->isRequired()) {
            $null = '|null';
        }

        $type = $this->getShortType($this->type);
        if ($this->isList) {
            return "list<$type>$null";
        }
        return "array<string, $type>$null";
    }

    public function getTypes(): array
    {
        return [$this->type];
    }
}
