<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;

use function array_filter;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OperationModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationModel
{
    /**
     * @param list<RequestBodyModel> $requestBodies
     */
    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        private readonly PathOrQueryParams|null $pathParams,
        private readonly PathOrQueryParams|null $queryParams,
        private readonly CookieOrHeaderParams|null $headerParams,
        private readonly CookieOrHeaderParams|null $cookieParams,
        private readonly array $requestBodies
    ) {
    }

    public function hasParameters(): bool
    {
        return $this->pathParams !== null
            || $this->queryParams !== null
            || $this->headerParams !== null
            || $this->cookieParams !== null
            || $this->requestBodies !== [];
    }

    /**
     * @return array{0?: ClassModel, 1?: ClassModel, 2?: ClassModel, 3?: ClassModel}
     */
    public function getModels(): array
    {
        return array_filter([
            $this->pathParams?->getModel(),
            $this->queryParams?->getModel(),
            $this->headerParams?->getModel(),
            $this->cookieParams?->getModel(),
        ]);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getPathParams(): ?PathOrQueryParams
    {
        return $this->pathParams;
    }

    public function getQueryParams(): ?PathOrQueryParams
    {
        return $this->queryParams;
    }

    public function getHeaderParams(): ?CookieOrHeaderParams
    {
        return $this->headerParams;
    }

    public function getCookieParams(): ?CookieOrHeaderParams
    {
        return $this->cookieParams;
    }

    /**
     * @return list<RequestBodyModel>
     */
    public function getRequestBodies(): array
    {
        return $this->requestBodies;
    }
}
