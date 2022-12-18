<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Schema;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\ModelTest
 */
final class Model
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
