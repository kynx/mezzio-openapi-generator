<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValueTest
 */
final class PropertyValue
{
    /** @param array<string, string> $valueMap */
    public function __construct(
        private readonly string|null $key,
        private readonly array $valueMap,
    ) {
    }

    public function getKey(): string|null
    {
        return $this->key;
    }

    public function getValueMap(): array
    {
        return $this->valueMap;
    }
}
