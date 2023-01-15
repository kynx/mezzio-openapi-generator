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
        private readonly string $key,
        private readonly array $valueMap,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array<string, string>
     */
    public function getValueMap(): array
    {
        return $this->valueMap;
    }
}
