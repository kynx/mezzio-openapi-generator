<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\DocumentContextInterface;
use cebe\openapi\spec\Reference;
use ReflectionClass;
use RuntimeException;
use Throwable;

use function get_debug_type;
use function sprintf;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelExceptionTest
 */
final class ModelException extends RuntimeException
{
    public static function invalidModelPath(string $path): self
    {
        return new self(sprintf("'%s' is not a valid path", $path));
    }

    public static function modelExists(AbstractClassLikeModel|EnumModel $model): self
    {
        return new self(sprintf(
            "Model '%s' already exists",
            $model->getClassName()
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

    public static function missingDocumentContext(): self
    {
        return new self("Specification is missing a document context");
    }

    public static function unresolvedReference(Reference $reference): self
    {
        return new self(sprintf(
            "Unresolved reference: '%s'",
            $reference->getReference()
        ));
    }

    public static function invalidSchemaItem(string $item, mixed $parent): self
    {
        $pointer = $parent instanceof DocumentContextInterface
            ? ($parent->getDocumentPosition()?->getPointer() ?? 'unknown')
            : 'unknown';
        return new self(sprintf(
            "Cannot parse $item at pointer '%s'",
            $pointer
        ));
    }
}
