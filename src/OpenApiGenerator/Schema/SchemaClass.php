<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use Kynx\Mezzio\OpenApi\OpenApiSchema;

final class SchemaClass
{
    public function __construct(private string $className, private OpenApiSchema $schema)
    {
    }

    public function matches(SchemaClass $schemaClass): bool
    {
        return $this->className === $schemaClass->className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getSchema(): OpenApiSchema
    {
        return $this->schema;
    }
}
