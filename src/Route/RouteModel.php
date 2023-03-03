<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

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
     */
    public function __construct(
        private readonly string $jsonPointer,
        private readonly string $path,
        private readonly string $method,
        private readonly array $pathParams,
        private readonly array $queryParams
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
}