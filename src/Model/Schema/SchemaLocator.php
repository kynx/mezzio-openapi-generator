<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_merge;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocatorTest
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator\Model\Schema
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator\Model\Schema
 */
final class SchemaLocator
{
    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $name, Schema $schema, array $visited = []): array
    {
        $pointer          = $schema->getDocumentPosition()?->getPointer() ?? '';
        if (in_array($pointer, $visited)) {
            return [];
        }
        $visited[] = $pointer;

        $name = ModelUtil::getComponentName('schemas', $schema) ?? $name;

        if ($schema->type === 'array' && $schema->items instanceof Schema) {
            return $this->getNamedSpecifications($name . 'Item', $schema->items, $visited);
        }
        if ($schema->additionalProperties instanceof Schema) {
            return $this->getNamedSpecifications($name . 'Item', $schema->additionalProperties, $visited);
        }

        $models = [];
        if (ModelUtil::isNamedSchema($schema)) {
            $models[$pointer] = new NamedSpecification($name, $schema);
        }

        if (! empty($schema->allOf)) {
            return array_merge($models, $this->getAllOfSchemas($name, $schema->allOf, $visited));
        }
        if (! empty($schema->anyOf)) {
            return array_merge($models, $this->getAnyOfSchemas($name, $schema->anyOf, $visited));
        }
        if (! empty($schema->oneOf)) {
            return array_merge($models, $this->getOneOfSchemas($name, $schema->oneOf, $visited));
        }

        foreach ($schema->properties as $propertyName => $property) {
            if ($property instanceof Reference) {
                throw ModelException::unresolvedReference($property);
            }
            $models = array_merge($models, $this->getNamedSpecifications("$name $propertyName", $property, $visited));
        }

        return $models;
    }

    /**
     * Only returns models for $composed schema if they are referenced
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, NamedSpecification>
     */
    private function getAllOfSchemas(string $name, array $composed, array $visited): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $allOf = $this->getNamedSpecifications($name . $i, $schema, $visited);
            if (! ModelUtil::isComponent('schemas', $schema)) {
                $pointer = $schema->getDocumentPosition()?->getPointer() ?? '';
                unset($allOf[$pointer]);
            }

            $models = array_merge($models, $allOf);
        }

        return $models;
    }

    /**
     * Does not return models for any of the $composed schemas: `anyOf` is just a bag of non-required properties
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, NamedSpecification>
     */
    private function getAnyOfSchemas(string $name, array $composed, array $visited): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $pointer = $schema->getDocumentPosition()?->getPointer() ?? '';
            $anyOf   = $this->getNamedSpecifications($name . $i, $schema, $visited);
            unset($anyOf[$pointer]);

            $models = array_merge($models, $anyOf);
        }

        return $models;
    }

    /**
     * Returns models for all $composed schemas, referenced or not
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, NamedSpecification>
     */
    private function getOneOfSchemas(string $name, array $composed, array $visited): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $models = array_merge($models, $this->getNamedSpecifications($name . $i, $schema, $visited));
        }

        return $models;
    }
}
