<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Security;

use Kynx\Mezzio\Authentication\ApiKey\ApiKeyAuthentication;

final class ApiKeySecurityModel implements SecurityModelInterface
{
    public function __construct(private readonly string $headerName, private readonly array $scopes = [])
    {
    }

    public function getAuthenticationAdapter(): string
    {
        return ApiKeyAuthentication::class;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function withScopes(array $scopes): static
    {
        return new self($this->headerName, $scopes);
    }
}
