<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Discriminator;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamerInterface;

use function array_combine;
use function array_keys;
use function array_merge;
use function assert;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OpenApiParserTest
 */
final class OpenApiParser
{
    public function __construct(
        private readonly OpenApi $openApi,
        private readonly NamerInterface $classNamer,
        private readonly UniqueVariableLabeler $propertyLabeler,
        private readonly UniqueConstantLabeler $caseLabeler
    ) {
    }

    public function getModelCollection(): ModelCollection
    {
        $collection = new ModelCollection();

        foreach ($this->getModelClasses() as $schemaClass) {
            $collection->add($schemaClass);
        }

        return $collection;
    }

    /**
     * @return list<EnumModel|ClassModel>
     */
    private function getModelClasses(): array
    {
        if ($this->openApi->components === null || $this->openApi->components->schemas === null) {
            return [];
        }

        $unresolved = $this->getUnresolved();
        $names = $unresolved->getNames();
        $classNames = array_combine(array_keys($names), array_keys($this->classNamer->keyByUniqueName($names)));

        $resolved = [];
        foreach ($unresolved->getDependents() as $dependent) {
            $resolved[] = $this->resolve($dependent, $classNames);
        }

        return $resolved;
    }

    private function getUnresolved(): UnresolvedModel
    {
        assert($this->openApi->components !== null);

        $dependents = [];
        foreach ($this->openApi->components->schemas as $name => $schema) {
            assert($schema instanceof Schema);
            if (! Util::isObject($schema)) {
                continue;
            }

            $dependents = array_merge($dependents, $this->getUnresolvedSchema($schema, '', $name));
        }
        return new UnresolvedModel('', '', null, ...$dependents);
    }

    private function getUnresolvedSchema(Schema $schema, string $baseName, string $name): array
    {
        if (! empty($schema->allOf)) {
            return $this->getUnresolvedComposite($schema, $schema->allOf, $baseName, $name);
        } elseif (! empty($schema->anyOf)) {
            return $this->getUnresolvedComposite($schema, $schema->anyOf, $baseName, $name);
        } elseif (! empty($schema->oneOf)) {
            return $this->getUnresolvedOneOf($schema, $baseName, $name);
        }

        return [new UnresolvedModel(
            '',
            $name,
            $schema,
            ...$this->getUnresolvedProperties($schema, $name)
        )];
    }

    /**
     * @param array<array-key, Schema> $components
     */
    private function getUnresolvedComposite(
        Schema $schema,
        array $components,
        string $baseName,
        string $name
    ): array {
        $properties = [];

        foreach ($components as $component) {
            $properties = array_merge(
                $properties,
                $this->getUnresolvedProperties($component, "$baseName $name")
            );
        }

        return [new UnresolvedModel($baseName, $name, $schema, ...$properties)];
    }

    private function getUnresolvedOneOf(Schema $schema, string $baseName, string $name): array
    {
        $models = [];
        foreach ($schema->oneOf as $component) {
            $models[] = $this->getUnresolvedSchema($component, $baseName, $name);
        }

        return $models;
    }

    /**
     * @return list<UnresolvedModel>
     */
    private function getUnresolvedProperties(Schema $schema, string $baseName): array
    {
        $dependents = [];
        foreach ($schema->properties as $name => $schema) {
            assert($schema instanceof Schema);
            if (! Util::isObject($schema)) {
                continue;
            }

            $dependents[] = new UnresolvedModel(
                $baseName,
                $name,
                $schema,
                ...$this->getUnresolvedProperties($schema, "$baseName $name")
            );
        }

        return $dependents;
    }

    /**
     * @param array<string, string> $classNames
     */
    private function resolve(UnresolvedModel $unresolved, array $classNames): EnumModel|ClassModel
    {
        $jsonPointer = $unresolved->getJsonPointer();
        assert(isset($classNames[$jsonPointer]));
        $className = $classNames[$jsonPointer];
        $schema = $unresolved->getSchema();
        assert($schema instanceof Schema);

        if (Util::isEnum($schema)) {
            return new EnumModel($className, $jsonPointer, ...$this->getCases($schema));
        }

        return new ClassModel(
            $className,
            $jsonPointer,
            ...$this->getProperties($schema, $classNames, $schema->required)
        );
    }

    /**
     * @param array<string, string> $classNames
     * @return array<string, Property>
     */
    private function getProperties(Schema $schema, array $classNames, array $required = null): array
    {
        if (! empty($schema->allOf) || ! empty($schema->anyOf)) {
            return $this->getCompositeProperties($schema, $classNames);
        }

        $properties = [];
        $required = $required ?? $schema->required ?? [];
        $names = $this->propertyLabeler->getUnique(array_keys($schema->properties));
        /** @var string $original */
        foreach ($names as $original => $name) {
            assert(isset($schema->properties[$original]) && $schema->properties[$original] instanceof Schema);
            $propertySchema  = $schema->properties[$original];
            $types = $this->getPropertyTypes($propertySchema, $classNames);
            $properties[$original] = new Property($name, in_array($original, $required), $types);
        }

        return $properties;
    }

    /**
     * @return array<string, Property>
     */
    private function getCompositeProperties(Schema $schema, array $classNames): array
    {
        $properties = [];

        if (! empty($schema->allOf)) {
            $allRequired = $this->getDiscriminatorRequired($schema);
            foreach ($schema->allOf as $component) {
                assert($component instanceof Schema);
                $required = array_merge($allRequired, $component->required ?? []);
                $properties = array_merge($properties, $this->getProperties($component, $classNames, $required));
            }
        } elseif (! empty($schema->anyOf)) {
            $required = $this->getDiscriminatorRequired($schema);
            foreach ($schema->anyOf as $component) {
                assert($component instanceof Schema);
                $properties = array_merge($properties, $this->getProperties($component, $classNames, $required));
            }
        }

        return $properties;
    }

    /**
     * @return list<string>
     */
    private function getDiscriminatorRequired(Schema $schema): array
    {
        if ($schema->discriminator instanceof Discriminator) {
            return [$schema->discriminator->propertyName];
        }

        return [];
    }

    private function getPropertyTypes(Schema $propertySchema, array $classNames): array|string|PropertyType
    {
        $pointer = Util::getJsonPointer($propertySchema);
        if (isset($classNames[$pointer])) {
            return $classNames[$pointer];
        }

        if (! empty($propertySchema->enum) && empty($propertySchema->type)) {
            $types = [];
            foreach ($propertySchema->enum as $value) {
                $type = PropertyType::fromValue($value, $propertySchema->nullable);
                if (! in_array($type, $types, true)) {
                    $types[] = $type;
                }
            }
            return $types;
        }

        if (! empty($propertySchema->oneOf)) {
            $types = [];
            foreach ($propertySchema->oneOf as $component) {
                $pointer = Util::getJsonPointer($component);
                if (isset($classNames[$pointer])) {
                    $types[] = $classNames[$pointer];
                    continue;
                }
                $subtype = $this->getPropertyTypes($component, $classNames);
                if (is_array($subtype)) {
                    $types = array_merge($types, $subtype);
                } else {
                    $types[] = $subtype;
                }
            }
            return $types;
        }

        $type = PropertyType::fromSchema($propertySchema);
        return $propertySchema->nullable ? [$type, PropertyType::Null] : $type;
    }

    /**
     * @return array<string, EnumCase>
     */
    private function getCases(Schema $schema): array
    {
        $cases = [];
        $names = $this->caseLabeler->getUnique($schema->enum);
        foreach ($names as $original => $case) {
            $cases[] = new EnumCase($case, $original);
        }

        return $cases;
    }
}
