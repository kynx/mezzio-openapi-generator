<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_merge;
use function array_pop;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocatorTest
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
        if ($schema->type === 'object' || $schema->allOf || $schema->anyOf) {
            $pointer          = $schema->getDocumentPosition()?->getPointer() ?? '';
            $models[$pointer] = new Model($name, $schema);
        }

        if (! empty($schema->allOf)) {
            return array_merge($models, $this->getComposedModels($name, $schema->allOf));
        }
        if (! empty($schema->anyOf)) {
            return array_merge($models, $this->getComposedModels($name, $schema->anyOf));
        }
        if (! empty($schema->oneOf)) {
            return array_merge($models, $this->getComposedModels($name, $schema->oneOf));
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
     * @param array<array-key, Schema|Reference> $composed
     * @return array<string, Model>
     */
    private function getComposedModels(string $name, array $composed): array
    {
        $schemas = [];
        foreach ($composed as $i => $schema) {
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }

            $schemas = array_merge($schemas, $this->getModels($name . $i, $schema));
        }

        return $schemas;
    }

    private function isReferenced(Schema $schema): bool
    {
        if ($schema->type !== 'object') {
            return false;
        }

        return $schema->getDocumentPosition()?->parent()?->getPointer() === '/components/schemas';
    }
}
