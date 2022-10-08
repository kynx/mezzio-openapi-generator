<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use DomainException;
use ReflectionClass;
use Throwable;

use function sprintf;

final class HandlerException extends DomainException
{
    public static function invalidHandlerPath(string $path): self
    {
        return new self(sprintf("'%s' is not a valid path", $path));
    }

    public static function handlerExists(HandlerClass $handlerClass): self
    {
        return new self(sprintf(
            "Handler class '%s' already exists",
            $handlerClass->getClassName()
        ));
    }

    public static function invalidOpenApiOperation(ReflectionClass $classReflection, Throwable $e): self
    {
        return new self(sprintf(
            "Invalid OpenApiOperation attribute for class '%s'",
            $classReflection->getName()
        ), 0, $e);
    }
}
