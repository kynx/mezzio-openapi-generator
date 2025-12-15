<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\DiscriminatorBuilder;

use function array_pop;
use function assert;
use function count;
use function in_array;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PropertyBuilder
{
    public function __construct(
        private readonly TypeMapper $typeMapper,
        private readonly DiscriminatorBuilder $discriminatorBuilder = new DiscriminatorBuilder()
    ) {
    }

    /**
     * @param array<string, string> $classNames
     */
    public function getProperty(
        Schema $schema,
        string $name,
        string $originalName,
        bool $required,
        array $classNames
    ): PropertyInterface {
        $pointer  = ModelUtil::getJsonPointer($schema);
        $metadata = $this->getMetadata($schema, $required);

        if (isset($classNames[$pointer])) {
            $classString = new ClassString($classNames[$pointer], ModelUtil::isEnum($schema));
            return new SimpleProperty($name, $originalName, $metadata, $classString);
        }

        if (! empty($schema->enum) && empty($schema->type)) {
            $types = [];
            /** @var mixed $value */
            foreach ($schema->enum as $value) {
                $type = PropertyType::fromValue($value);
                if (! in_array($type, $types, true)) {
                    $types[] = $type;
                }
            }

            if (count($types) > 1) {
                return new UnionProperty($name, $originalName, $metadata, null, ...$types);
            }

            $type = array_pop($types);
            assert($type instanceof PropertyType);
            return new SimpleProperty($name, $originalName, $metadata, $type);
        }

        if (! empty($schema->oneOf)) {
            $types = [];
            foreach ($schema->oneOf as $component) {
                assert($component instanceof Schema);
                $types[] = $this->getPropertyTypeOrClassName($component, $classNames);
            }
            $discriminator = $this->discriminatorBuilder->getDiscriminator($schema, $classNames);
            return new UnionProperty($name, $originalName, $metadata, $discriminator, ...$types);
        }

        if ($schema->additionalProperties instanceof Schema) {
            $type = $this->getPropertyTypeOrClassName($schema->additionalProperties, $classNames);
            return new ArrayProperty($name, $originalName, $metadata, false, $type);
        }

        if ($schema->items instanceof Schema) {
            $type = $this->getPropertyTypeOrClassName($schema->items, $classNames);
            return new ArrayProperty($name, $originalName, $metadata, true, $type);
        }

        $type = $this->getPropertyTypeOrClassName($schema, $classNames);
        return new SimpleProperty($name, $originalName, $metadata, $type);
    }

    private function getMetadata(Schema $schema, bool $required): PropertyMetadata
    {
        /**
         * `example` is deprecated but upstream doesn't support `examples` yet
         *
         * @link https://spec.openapis.org/oas/v3.1.0#fixed-fields-19
         */
        $examples = (array) ($schema->examples ?? (array) ($schema->example ?? []));

        /**
         * @psalm-suppress RedundantConditionGivenDocblockType Upstream docblocks are wrong
         * @psalm-suppress DocblockTypeContradiction           Ditto
         */
        return new PropertyMetadata(
            $schema->title ?? '',
            $schema->description ?? '',
            $required,
            $schema->nullable ?? false,
            $schema->readOnly ?? false,
            $schema->writeOnly ?? false,
            $schema->deprecated ?? false,
            $schema->default ?? null,
            $examples
        );
    }

    /**
     * @param array<string, string> $classNames
     */
    private function getPropertyTypeOrClassName(Schema $schema, array $classNames): PropertyType|ClassString
    {
        $pointer = ModelUtil::getJsonPointer($schema);
        if (isset($classNames[$pointer])) {
            return new ClassString($classNames[$pointer], ModelUtil::isEnum($schema));
        }
        return $this->typeMapper->map($schema);
    }
}
