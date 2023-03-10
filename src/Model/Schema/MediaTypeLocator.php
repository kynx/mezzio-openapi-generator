<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_merge;
use function array_unique;
use function count;
use function preg_replace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model\Schema
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model\Schema
 */
final class MediaTypeLocator
{
    public function __construct(private readonly SchemaLocator $schemaLocator = new SchemaLocator())
    {
    }

    /**
     * @param array<array-key, MediaType> $mediaTypes
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $baseName, array $mediaTypes): array
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
                $name = $baseName . ' ' . preg_replace('/[^a-z0-9]+/i', ' ', (string) $type);
            } else {
                $name = $baseName;
            }

            $models = array_merge($models, $this->schemaLocator->getNamedSpecifications($name, $mediaType->schema));
        }

        return $models;
    }
}
