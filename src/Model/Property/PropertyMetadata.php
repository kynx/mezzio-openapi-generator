<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @link https://datatracker.ietf.org/doc/html/draft-bhutton-json-schema-validation-00#section-9
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadataTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PropertyMetadata
{
    public function __construct(
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly bool $required = false,
        private readonly bool $nullable = false,
        private readonly bool $readOnly = false,
        private readonly bool $writeOnly = false,
        private readonly bool $deprecated = false,
        private readonly mixed $default = null,
        private readonly array $examples = []
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isWriteOnly(): bool
    {
        return $this->writeOnly;
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getExamples(): array
    {
        return $this->examples;
    }
}
