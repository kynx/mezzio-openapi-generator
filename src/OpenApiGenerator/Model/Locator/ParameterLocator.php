<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocatorTest
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
     * @return array<string, Model>
     */
    public function getModels(string $baseName, Parameter $parameter): array
    {
        $name = $baseName . ' ' . $parameter->name . 'Param';

        if ($parameter->schema instanceof Reference) {
            throw ModelException::unresolvedReference($parameter->schema);
        }
        if ($parameter->schema instanceof Schema) {
            return $this->schemaLocator->getModels($name, $parameter->schema);
        }

        return $this->mediaTypeLocator->getModels($name, $parameter->content);
    }
}
