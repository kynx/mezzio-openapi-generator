<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\UniqueVariableLabeler;

use function array_keys;
use function array_merge;
use function assert;
use function in_array;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class PropertiesBuilder
{
    public function __construct(
        private readonly UniqueVariableLabeler $propertyLabeler,
        private readonly PropertyBuilder $propertyBuilder = new PropertyBuilder()
    ) {
    }

    /**
     * @param array<string, string> $classNames
     * @return list<PropertyInterface>
     */
    public function getProperties(Schema $schema, array $classNames): array
    {
        if (! empty($schema->allOf) || ! empty($schema->anyOf)) {
            return $this->getCompositeProperties($schema, $classNames);
        }

        return $this->getSimpleProperties($schema, $classNames, null);
    }

    /**
     * @param array<string, string> $classNames
     * @return list<PropertyInterface>
     */
    private function getCompositeProperties(Schema $schema, array $classNames): array
    {
        $properties = [];
        $components = [];
        $required   = null;

        if (! empty($schema->allOf)) {
            $components = $schema->allOf;
        } elseif (! empty($schema->anyOf)) {
            $components = $schema->anyOf;
            $required   = []; // override schema's required properties
        }

        foreach ($components as $component) {
            assert($component instanceof Schema);
            $properties = array_merge($properties, $this->getSimpleProperties($component, $classNames, $required));
        }

        return $properties;
    }

    /**
     * @param array<string, string> $classNames
     * @return list<PropertyInterface>
     */
    private function getSimpleProperties(Schema $schema, array $classNames, array|null $required): array
    {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType Upstream docblock is wrong
         * @psalm-suppress DocblockTypeContradiction           Ditto - it's nullable
         */
        $required = $required ?? $schema->required ?? [];
        /** @var list<string> $properyNames */
        $properyNames = array_keys($schema->properties);
        $names        = $this->propertyLabeler->getUnique($properyNames);
        $properties   = [];

        /**
         * @var string $original
         * @var string $name
         */
        foreach ($names as $original => $name) {
            assert(isset($schema->properties[$original]) && $schema->properties[$original] instanceof Schema);
            $propertySchema = $schema->properties[$original];
            $isRequired     = in_array($original, $required, true);
            $properties[]   = $this->propertyBuilder->getProperty(
                $propertySchema,
                $name,
                $original,
                $isRequired,
                $classNames
            );
        }

        return $properties;
    }
}
