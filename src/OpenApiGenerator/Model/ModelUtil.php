<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;

use function assert;
use function in_array;

final class ModelUtil
{
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
