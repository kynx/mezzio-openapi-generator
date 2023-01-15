<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

final class PropertyList
{
    /**
     * @param array<string, list<string>> $classMap maps classes to list of properties
     */
    public function __construct(private readonly array $classMap)
    {
    }

    /**
     * @return array<string, list<string>>
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }
}
