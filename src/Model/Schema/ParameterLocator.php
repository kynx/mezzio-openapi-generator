<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

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
    public function __construct(
        private readonly MediaTypeLocator $mediaTypeLocator = new MediaTypeLocator(),
        private readonly SchemaLocator $schemaLocator = new SchemaLocator()
    ) {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $baseName, Parameter $parameter): array
    {
        $name = $baseName . ' ' . $parameter->name . 'Param';

        if ($parameter->schema instanceof Reference) {
            throw ModelException::unresolvedReference($parameter->schema);
        }
        if ($parameter->schema instanceof Schema) {
            return $this->schemaLocator->getNamedSpecifications($name, $parameter->schema);
        }

        return $this->mediaTypeLocator->getNamedSpecifications($name, $parameter->content);
    }
}
