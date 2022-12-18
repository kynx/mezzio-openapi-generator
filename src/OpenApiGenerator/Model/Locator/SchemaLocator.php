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
     * @return array<string, Model>
     */
    public function getModels(string $name, Schema $schema): array
    {
        if ($this->isReferenced($schema)) {
            $paths = $schema->getDocumentPosition()?->getPath() ?? [];
            $name  = (string) array_pop($paths);
        }

        if ($schema->type === 'array' && $schema->items instanceof Schema) {
            return $this->getModels($name . 'Item', $schema->items);
        }
        if ($schema->additionalProperties instanceof Schema) {
            return $this->getModels($name . 'Item', $schema->additionalProperties);
        }

        $models = [];
        if ($this->isModel($schema)) {
            $pointer          = $schema->getDocumentPosition()?->getPointer() ?? '';
            $models[$pointer] = new Model($name, $schema);
        }

        if (! empty($schema->allOf)) {
            return array_merge($models, $this->getAllOfModels($name, $schema->allOf));
        }
        if (! empty($schema->anyOf)) {
            return array_merge($models, $this->getAnyOfModels($name, $schema->anyOf));
        }
        if (! empty($schema->oneOf)) {
            return array_merge($models, $this->getOneOfModels($name, $schema->oneOf));
        }

        foreach ($schema->properties as $propertyName => $property) {
            if (! $property instanceof Schema) {
                continue;
            }
            $models = array_merge($models, $this->getModels("$name $propertyName", $property));
        }

        return $models;
    }

    /**
     * Only returns models for $composed schema if they are referenced
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, Model>
     */
    private function getAllOfModels(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $allOf = $this->getModels($name . $i, $schema);
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
     * @return array<string, Model>
     */
    private function getAnyOfModels(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $pointer = $schema->getDocumentPosition()?->getPointer() ?? '';
            $anyOf   = $this->getModels($name . $i, $schema);
            unset($anyOf[$pointer]);

            $models = array_merge($models, $anyOf);
        }

        return $models;
    }

    /**
     * Returns models for all $composed schemas, referenced or not
     *
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, Model>
     */
    private function getOneOfModels(string $name, array $composed): array
    {
        $models = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $models = array_merge($models, $this->getModels($name . $i, $schema));
        }

        return $models;
    }

    private function isReferenced(Schema $schema): bool
    {
        if (! $this->isModel($schema)) {
            return false;
        }

        return $schema->getDocumentPosition()?->parent()?->getPointer() === '/components/schemas';
    }

    private function isModel(Schema $schema): bool
    {
        return $schema->type === 'object' || $schema->allOf || $schema->anyOf || ModelUtil::isEnum($schema);
    }
}
