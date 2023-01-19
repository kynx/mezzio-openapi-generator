<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;

use function array_map;
use function assert;

final class DiscriminatorUtil
{
    private function __construct()
    {
    }

    /**
     * @param array<string, string> $hydratorMap
     * @return array{key: string, map: array<string, string>}
     */
    public static function getValueDiscriminator(UnionProperty $property, array $hydratorMap): array
    {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyValue);

        $valueMap = array_map(
            fn (string $classString): string => $hydratorMap[$classString],
            $discriminator->getValueMap()
        );

        return [
            'key' => $discriminator->getKey(),
            'map' => $valueMap,
        ];
    }

    /**
     * @param array<string, string> $hydratorMap
     * @return array<string, list<string>>
     */
    public static function getListDiscriminator(UnionProperty $property, array $hydratorMap): array
    {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyList);

        $classMap = [];
        foreach ($discriminator->getClassMap() as $classString => $properties) {
            $fullyQualified            = $hydratorMap[$classString];
            $classMap[$fullyQualified] = $properties;
        }

        return $classMap;
    }
}
