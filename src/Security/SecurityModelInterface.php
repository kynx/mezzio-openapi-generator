<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Security;

use Mezzio\Authentication\AuthenticationInterface;

interface SecurityModelInterface
{
    /**
     * @return class-string<AuthenticationInterface>
     */
    public function getAuthenticationAdapter(): string;

    public function getScopes(): array;

    public function withScopes(array $scopes): static;
}
