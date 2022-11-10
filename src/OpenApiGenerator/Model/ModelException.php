<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use ReflectionClass;
use RuntimeException;

use Throwable;

use function get_debug_type;
use function sprintf;

final class ModelException extends RuntimeException
{
    public static function invalidModelPath(string $path): self
    {
        return new self(sprintf("'%s' is not a valid path", $path));
    }

    public static function schemaExists(ClassModel $schemaClass): self
    {
        return new self(sprintf(
            "Model class '%s' already exists",
            $schemaClass->getClassName()
        ));
    }

    public static function unrecognizedType(string $type): self
    {
        return new self("Unrecognized type '$type'");
    }

    public static function unrecognizedValue(mixed $value): self
    {
        return new self(sprintf("Unrecognized value '%s'", get_debug_type($value)));
    }

    public static function invalidOpenApiSchema(ReflectionClass $class, Throwable $e): self
    {
        return new self(sprintf(
            "Invalid OpenApiSchema attribute for class '%s'",
            $class->getName()
        ), 0, $e);
    }

    public static function circularReference(UnresolvedModel $node, UnresolvedModel $dependent): self
    {
        return new self(sprintf(
            "Circular reference detected %s -> %s",
            $node->getJsonPointer(),
            $dependent->getJsonPointer()
        ));
    }
}
