<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteModelTest
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteModel
{
    /**
     * @param list<ParameterModel> $pathParams
     * @param list<ParameterModel> $queryParams
     * @param list<class-string> $middleware
     */
    public function __construct(
        private readonly string $jsonPointer,
        private readonly string $path,
        private readonly string $method,
        private readonly array $pathParams,
        private readonly array $queryParams,
        private readonly ?SecurityModelInterface $securityModel,
        private readonly array $middleware
    ) {
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return list<ParameterModel>
     */
    public function getPathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * @return list<ParameterModel>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getSecurityModel(): ?SecurityModelInterface
    {
        return $this->securityModel;
    }

    /**
     * @return list<class-string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
