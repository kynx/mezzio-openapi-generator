<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;

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

    public static function isEnum(Schema $schema): bool
    {
        return $schema->type === 'string' && ! empty($schema->enum);
    }
}
