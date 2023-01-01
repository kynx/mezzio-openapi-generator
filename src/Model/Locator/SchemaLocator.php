<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;

use function array_merge;
use function array_pop;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocatorTest
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator\Model\Locator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator\Model\Locator
 */
final class SchemaLocator
{
    /**
     * @return array<string, NamedSchema>
     */
    public function getNamedSchemas(string $name, Schema $schema): array
    {
        if ($this->isReferenced($schema)) {
            $paths = $schema->getDocumentPosition()?->getPath() ?? [];
            $name  = (string) array_pop($paths);
        }

        if ($schema->type === 'array' && $schema->items instanceof Schema) {
            return $this->getNamedSchemas($name . 'Item', $schema->items);
        }
        if ($schema->additionalProperties instanceof Schema) {
            return $this->getNamedSchemas($name . 'Item', $schema->additionalProperties);
        }

        $models = [];
        if ($this->isNamedSchema($schema)) {
            $pointer          = $schema->getDocumentPosition()?->getPointer() ?? '';
            $models[$pointer] = new NamedSchema($name, $schema);
        }

        if (! empty($schema->allOf)) {
            return array_merge($models, $this->getAllOfSchemas($name, $schema->allOf));
        }
        if (! empty($schema->anyOf)) {
            return array_merge($models, $this->getAnyOfSchemas($name, $schema->anyOf));
        }
        if (! empty($schema->oneOf)) {
            return array_merge($models, $this->getOneOfSchemas($name, $schema->oneOf));
        }

        foreach ($schema->properties as $propertyName => $property) {
            if ($property instanceof Reference) {
                throw ModelException::unresolvedReference($property);
            }
            $models = array_merge($models, $this->getNamedSchemas("$name $propertyName", $property));
        }

        return $models;
    }

    /**
     * Only returns models for $composed schema if they are referenced
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, NamedSchema>
     */
    private function getAllOfSchemas(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $allOf = $this->getNamedSchemas($name . $i, $schema);
            if (! $this->isReferenced($schema)) {
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
     * @return array<string, NamedSchema>
     */
    private function getAnyOfSchemas(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $pointer = $schema->getDocumentPosition()?->getPointer() ?? '';
            $anyOf   = $this->getNamedSchemas($name . $i, $schema);
            unset($anyOf[$pointer]);

            $models = array_merge($models, $anyOf);
        }

        return $models;
    }

    /**
     * Returns models for all $composed schemas, referenced or not
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, NamedSchema>
     */
    private function getOneOfSchemas(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $models = array_merge($models, $this->getNamedSchemas($name . $i, $schema));
        }

        return $models;
    }

    private function isReferenced(Schema $schema): bool
    {
        if (! $this->isNamedSchema($schema)) {
            return false;
        }

        return $schema->getDocumentPosition()?->parent()?->getPointer() === '/components/schemas';
    }

    private function isNamedSchema(Schema $schema): bool
    {
        return $schema->type === 'object' || $schema->allOf || $schema->anyOf || ModelUtil::isEnum($schema);
    }
}
