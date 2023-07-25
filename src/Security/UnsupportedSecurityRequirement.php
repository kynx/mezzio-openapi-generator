<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Security;

use RuntimeException;

final class UnsupportedSecurityRequirement extends RuntimeException
{
    public static function multipleSecurityRequirements(): self
    {
        return new self("Multiple security requirements are not supported");
    }

    public static function unsupportedRequirement(string $securityRequirement): self
    {
        return new self("Security requirement '$securityRequirement' is not supported");
    }

    public static function nonExistentSecurityScheme(string $name): self
    {
        return new self("Security scheme name '$name' does not exist in specification");
    }
}