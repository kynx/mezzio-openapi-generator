<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Security;

use Mezzio\Authentication\Basic\BasicAccess;

final class BasicSecurityModel implements SecurityModelInterface
{
    public function __construct(private string $scheme, private array $scopes = [])
    {
    }

    public function getAuthenticationAdapter(): string
    {
        return BasicAccess::class;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function withScopes(array $scopes): static
    {
        return new self($this->scheme, $scopes);
    }
}
