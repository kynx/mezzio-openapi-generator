<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use RuntimeException;

use function sprintf;

final class SchemaException extends RuntimeException
{
    public static function schemaExists(SchemaClass $schemaClass): self
    {
        return new self(sprintf(
            "Schema class '%s' already exists",
            $schemaClass->getClassName()
        ));
    }
}
