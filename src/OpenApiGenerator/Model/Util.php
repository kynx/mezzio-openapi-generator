<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

use function in_array;

final class Util
{
    public static function getJsonPointer(Schema $schema): string
    {
        $position = $schema->getDocumentPosition();
        assert($position instanceof JsonPointer);
        return $position->getPointer();
    }

    /**
     * @psalm-assert-if-true Schema $schema
     */
    public static function isObject(Schema|Reference $schema): bool
    {
        if (! $schema instanceof Schema) {
            return false;
        }

        return $schema->type === 'object'
            || self::isComposite($schema)
            || self::isEnum($schema);
    }

    /**
     * @psalm-assert-if-true Schema $schema
     */
    public static function isEnum(Schema|Reference|null $schema): bool
    {
        if (! $schema instanceof Schema) {
            return false;
        }

        return in_array($schema->type, ['integer', 'string'], true) && ! empty($schema->enum);
    }

    /**
     * @psalm-assert-if-true Schema $schema
     */
    public static function isComposite(Schema|Reference $schema): bool
    {
        if (! $schema instanceof Schema) {
            return false;
        }

        return ! empty($schema->allOf)
            || ! empty($schema->anyOf);
    }

    /**
     * @psalm-assert-if-true Schema $schema
     */
    public static function isComponent(Schema|Reference $schema): bool
    {
        if (! $schema instanceof Schema) {
            return false;
        }

        return $schema->getDocumentPosition()?->parent()?->getPointer() === '/components/schemas';
    }
}
