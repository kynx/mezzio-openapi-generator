<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ParameterLocator
{
    private MediaTypeLocator $mediaTypeLocator;
    private SchemaLocator $schemaLocator;

    public function __construct()
    {
        $this->mediaTypeLocator = new MediaTypeLocator();
        $this->schemaLocator    = new SchemaLocator();
    }

    /**
     * @return array<string, NamedSchema>
     */
    public function getNamedSchemas(string $baseName, Parameter $parameter): array
    {
        $name = $baseName . ' ' . $parameter->name . 'Param';

        if ($parameter->schema instanceof Reference) {
            throw ModelException::unresolvedReference($parameter->schema);
        }
        if ($parameter->schema instanceof Schema) {
            return $this->schemaLocator->getNamedSchemas($name, $parameter->schema);
        }

        return $this->mediaTypeLocator->getNamedSchemas($name, $parameter->content);
    }
}
