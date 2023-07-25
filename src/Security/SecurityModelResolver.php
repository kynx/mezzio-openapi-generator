<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Security;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\SecurityRequirement;
use cebe\openapi\spec\SecurityScheme;

use function array_filter;
use function array_key_first;
use function current;
use function get_object_vars;

final class SecurityModelResolver
{
    /** @var array<string, SecurityModelInterface> */
    private readonly array $securityModels;
    private readonly ?SecurityModelInterface $globalRequirement;

    public function __construct(OpenApi $openApi)
    {
        $models = [];
        foreach ($openApi->components?->securitySchemes ?? [] as $name => $securityScheme) {
            $models[$name] = $this->createSecurityModel($securityScheme);
        }
        $this->securityModels = $models;

        if (empty($openApi->security)) {
            $this->globalRequirement = null;
        } else {
            $this->globalRequirement = $this->resolve($openApi->security);
        }
    }

    /**
     * @param array<array-key, SecurityRequirement> $security
     */
    public function resolve(?array $security): ?SecurityModelInterface
    {
        if ($security === null) {
            return $this->globalRequirement;
        }

        if ($security === []) {
            return null;
        }

        if (count($security) > 1) {
            throw UnsupportedSecurityRequirement::multipleSecurityRequirements();
        }

        $requirements = array_map(
            static fn (SecurityRequirement $requirement): array => get_object_vars($requirement->getSerializableData()),
            $security
        );

        $requirement = current($requirements);
        return $this->getSecurityModel(array_key_first($requirement))->withScopes(current($requirement));
    }

    private function getSecurityModel(string $name): SecurityModelInterface
    {
        if (! isset($this->securityModels[$name])) {
            throw UnsupportedSecurityRequirement::nonExistentSecurityScheme($name);
        }

        return $this->securityModels[$name];
    }

    private function createSecurityModel(SecurityScheme $securityScheme): SecurityModelInterface
    {
        return match ($securityScheme->type) {
            "apiKey" => new ApiKeySecurityModel($securityScheme->name),
            "http"   => new BasicSecurityModel($securityScheme->scheme),
            default  => throw UnsupportedSecurityRequirement::unsupportedRequirement($securityScheme->type)
        };
    }
}
