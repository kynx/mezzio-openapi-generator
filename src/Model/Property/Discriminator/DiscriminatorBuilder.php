<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

use cebe\openapi\spec\Discriminator;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;

use function array_keys;
use function assert;
use function ltrim;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\Discriminator\DiscriminatorBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class DiscriminatorBuilder
{
    /**
     * @param array<string, string> $classNames
     */
    public function getDiscriminator(Schema $schema, array $classNames): PropertyList|PropertyValue|null
    {
        if (empty($schema->oneOf)) {
            return null;
        }

        $discriminator = $this->getSchemaDiscriminator($schema);
        if ($discriminator instanceof Discriminator) {
            return $this->getPropertyValueDiscriminator($schema, $discriminator, $classNames);
        }

        return $this->getPropertyListDiscriminator($schema, $classNames);
    }

    /**
     * @param array<string, string> $classNames
     */
    private function getPropertyValueDiscriminator(
        Schema $schema,
        Discriminator $discriminator,
        array $classNames
    ): PropertyValue {
        $valueMap = [];
        foreach ($schema->oneOf as $component) {
            $pointer = ModelUtil::getJsonPointer($component);
            assert(isset($classNames[$pointer]));

            $fullyQualified       = $classNames[$pointer];
            $className            = GeneratorUtil::getClassName($fullyQualified);
            $valueMap[$className] = $fullyQualified;
        }
        /** @var string $from */
        foreach ($discriminator->mapping as $from => $to) {
            $pointer = ltrim($to, '#');
            assert(isset($classNames[$pointer]));
            $valueMap[$from] = $classNames[$pointer];
        }

        return new PropertyValue($discriminator->propertyName, $valueMap);
    }

    /**
     * @param array<string, string> $classNames
     */
    private function getPropertyListDiscriminator(Schema $schema, array $classNames): PropertyList|null
    {
        $classMap = [];
        foreach ($schema->oneOf as $component) {
            assert($component instanceof Schema);

            $pointer = ModelUtil::getJsonPointer($component);

            // The spec _implies_ that all the components have to be referenced schemas, but only in reference to
            // the discriminator object. Are `oneOf` with simple schemas and no discriminator permitted?
            if (! isset($classNames[$pointer])) {
                return null;
            }
            $className = $classNames[$pointer];

            /** @var list<string> $propertyNames */
            $propertyNames        = array_keys($component->properties);
            $classMap[$className] = $propertyNames;
        }

        return new PropertyList($classMap);
    }

    /**
     * In the examples the discriminator can be present on either the `oneOf` or on one of the components. However
     * it also says that it "is legal only when using one of the composite keywords oneOf, anyOf, allOf", so it's
     * entirely unclear which takes precedence or even whether the component discriminator should be used. Grrr...
     *
     * @link https://spec.openapis.org/oas/v3.1.0#models-with-polymorphism-support
     */
    private function getSchemaDiscriminator(Schema $schema): Discriminator|null
    {
        if ($schema->discriminator instanceof Discriminator) {
            return $schema->discriminator;
        }

        foreach ($schema->oneOf as $component) {
            assert($component instanceof Schema);
            if ($component->discriminator instanceof Discriminator) {
                return $component->discriminator;
            }

            /** @psalm-suppress RedundantCastGivenDocblockType Docblock is wrong */
            foreach ((array) $component->allOf as $subSchema) {
                assert($subSchema instanceof Schema);
                if ($subSchema->discriminator instanceof Discriminator) {
                    return $subSchema->discriminator;
                }
            }
        }

        return null;
    }
}
