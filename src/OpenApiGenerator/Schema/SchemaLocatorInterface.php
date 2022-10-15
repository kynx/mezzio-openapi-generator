<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

interface SchemaLocatorInterface
{
    public function create(): SchemaCollection;
}
