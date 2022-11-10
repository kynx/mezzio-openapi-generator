<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

/**
 * @psalm-type Type string|PropertyType
 */
final class Property
{
    private array $subtypes;

    /**
     * @param list<Type>|Type $type
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $required,
        private readonly array|string|PropertyType $type,
        string|PropertyType ...$subtypes
    ) {
        $this->subtypes = $subtypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getType(): PropertyType|string
    {
        return $this->type;
    }

    public function getSubtypes(): array
    {
        return $this->subtypes;
    }
}