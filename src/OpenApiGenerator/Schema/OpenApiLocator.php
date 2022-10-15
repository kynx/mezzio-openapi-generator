<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\spec\OpenApi;

final class OpenApiLocator implements SchemaLocatorInterface
{
    public function __construct(private OpenApi $openApi)
    {
    }

    public function create(): SchemaCollection
    {
        $collection = new SchemaCollection();

        foreach ($this->getSchemaClasses() as $schemaClass) {
            $collection->add($schemaClass);
        }

        return $collection;
    }

    /**
     * @return list<SchemaClass>
     */
    private function getSchemaClasses(): array
    {
        $schemaClasses = [];

        foreach ($this->openApi->components->schemas as $name => $schema) {
        }

        return $schemaClasses;
    }
}
