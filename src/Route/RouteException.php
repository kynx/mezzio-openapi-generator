<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use DomainException;
use Exception;
use Kynx\Mezzio\OpenApi\OpenApiOperation;

use function sprintf;

final class RouteException extends DomainException
{
    public static function missingDelegator(string $delegatorClass, ?Exception $exception): self
    {
        return new self(sprintf("Cannot find route delegator '%s'", $delegatorClass), 0, $exception);
    }

    public static function missingOperation(OpenApiOperation $operation): self
    {
        return new self(sprintf(
            "Cannot find operation for path '%s' and method '%s'",
            $operation->getPath(),
            $operation->getMethod()
        ));
    }

    public static function missingHandler(string $path, string $method, ?string $operationId): self
    {
        return new self(sprintf(
            "No handler found for path '%s', method '%s', operationId %s",
            $path,
            $method,
            $operationId === null ? '`null`' : "'$operationId'"
        ));
    }
}
