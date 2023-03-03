<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function explode;
use function implode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationModel
{
    /**
     * @param list<RequestBodyModel> $requestBodies
     * @param list<ResponseModel> $responses
     */
    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        private readonly PathOrQueryParams|null $pathParams = null,
        private readonly PathOrQueryParams|null $queryParams = null,
        private readonly CookieOrHeaderParams|null $headerParams = null,
        private readonly CookieOrHeaderParams|null $cookieParams = null,
        private readonly array $requestBodies = [],
        private readonly array $responses = []
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

    public function getRequestClassName(): string
    {
        return GeneratorUtil::getNamespace($this->className) . '\\Request';
    }

    public function getRequestFactoryClassName(): string
    {
        return $this->getRequestClassName() . 'Factory';
    }

    public function getResponseFactoryClassName(): string
    {
        return GeneratorUtil::getNamespace($this->className) . '\\ResponseFactory';
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

    /**
     * @return array<int, string>
     */
    public function getRequestBodyUses(): array
    {
        $uses = [];
        foreach ($this->requestBodies as $requestBody) {
            $uses = array_merge($uses, $requestBody->getType()->getUses());
        }
        return array_unique($uses);
    }

    public function getRequestBodyType(): string
    {
        $types = [];
        foreach ($this->getRequestBodies() as $requestBody) {
            $types[] = $requestBody->getType()->getPhpType();
        }

        $allTypes = explode('|', implode('|', $types));
        return implode('|', array_unique($allTypes));
    }

    public function responsesRequireNegotiation(): bool
    {
        foreach ($this->getResponseStatuses() as $status) {
            if ($this->responseStatusRequiresNegotiation($status)) {
                return true;
            }
        }

        return false;
    }

    public function responseStatusRequiresNegotiation(string $status): bool
    {
        $mimeTypes = array_map(
            fn (ResponseModel $model): string|null => $model->getMimeType(),
            $this->getResponsesOfStatus($status)
        );
        return count($mimeTypes) > 1;
    }

    public function responsesRequireSerialization(): bool
    {
        foreach ($this->getResponseStatuses() as $status) {
            if ($this->responseStatusRequiresSerialization($status)) {
                return true;
            }
        }

        return false;
    }

    public function responseStatusRequiresSerialization(string $status): bool
    {
        foreach ($this->getResponsesOfStatus($status) as $response) {
            if ($response->getType() !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getResponseStatuses(): array
    {
        return array_unique(array_map(
            fn (ResponseModel $response): string => $response->getStatus(),
            $this->responses
        ));
    }

    /**
     * @return array<int, ResponseModel>
     */
    public function getResponsesOfStatus(string $status): array
    {
        return array_filter($this->responses, fn (ResponseModel $model): bool => $model->getStatus() === $status);
    }

    public function getResponseHeaders(string $status): array
    {
        foreach ($this->getResponsesOfStatus($status) as $response) {
            return $response->getHeaders();
        }

        return [];
    }
}
