<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

use cebe\openapi\SpecBaseObject;

use function array_pop;
use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelUtilTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelUtil
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function getJsonPointer(Schema|Reference $schema): string
    {
        if (! $schema instanceof Schema) {
            return '';
        }

        $position = $schema->getDocumentPosition();
        assert($position instanceof JsonPointer);
        return $position->getPointer();
    }

    public static function getComponentName(string $section, SpecBaseObject $specification): string|null
    {
        $pointer = $specification->getDocumentPosition()?->parent()?->getPointer();
        if ($pointer !== "/components/$section") {
            return null;
        }

        /** @var list<string> $paths */
        $paths = $specification->getDocumentPosition()?->getPath() ?? [];
        return rtrim($section, 's') . ' ' . (array_pop($paths) ?: '');
    }

    public static function isComponent(string $section, Schema $schema): bool
    {
        if (! self::isNamedSchema($schema)) {
            return false;
        }

        return $schema->getDocumentPosition()?->parent()?->getPointer() === "/components/$section";
    }

    public static function isNamedSchema(Schema $schema): bool
    {
        return $schema->type === 'object' || $schema->allOf || $schema->anyOf || ModelUtil::isEnum($schema);
    }

    public static function isEnum(Schema $schema): bool
    {
        return $schema->type === 'string' && ! empty($schema->enum);
    }
}
