<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Schema;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\NamedSchemaTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class NamedSchema
{
    public function __construct(private readonly string $name, private readonly Schema $schema)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getJsonPointer(): string
    {
        return $this->schema->getDocumentPosition()?->getPointer() ?? '';
    }
}
