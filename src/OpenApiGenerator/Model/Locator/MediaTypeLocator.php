<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_merge;
use function array_pop;
use function array_unique;
use function count;
use function explode;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model\Locator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model\Locator
 */
final class MediaTypeLocator
{
    private SchemaLocator $schemaLocator;

    public function __construct()
    {
        $this->schemaLocator = new SchemaLocator();
    }

    /**
     * @param array<array-key, MediaType> $mediaTypes
     * @return array<string, NamedSchema>
     */
    public function getModels(string $baseName, array $mediaTypes): array
    {
        $models   = [];
        $pointers = [];
        foreach ($mediaTypes as $mediaType) {
            $schema = $mediaType->schema;
            if ($schema instanceof Reference) {
                throw ModelException::unresolvedReference($schema);
            }
            if ($schema === null) {
                continue;
            }

            $pointers[] = $schema->getDocumentPosition()?->getPointer() ?? '';
        }

        $numPointers = count(array_unique($pointers));
        foreach ($mediaTypes as $type => $mediaType) {
            if (! $mediaType->schema instanceof Schema) {
                continue;
            }

            // add content type to name if they're not all pointing at same schema
            if ($numPointers > 1) {
                $types = explode('/', (string) $type);
                $name  = $baseName . ucfirst(array_pop($types));
            } else {
                $name = $baseName;
            }

            $models = array_merge($models, $this->schemaLocator->getModels($name, $mediaType->schema));
        }

        return $models;
    }
}
