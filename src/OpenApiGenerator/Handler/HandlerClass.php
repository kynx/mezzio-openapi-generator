<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerClassTest
 */
final class HandlerClass
{
    private string $className;
    private OpenApiOperation $operation;

    public function __construct(string $className, OpenApiOperation $operation)
    {
        $this->className = $className;
        $this->operation = $operation;
    }

    /**
     * Returns true if path and method match given operation
     */
    public function matches(OpenApiOperation $operation): bool
    {
        return $this->operation->getPath() === $operation->getPath()
            && $this->operation->getMethod() === $operation->getMethod();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getOperation(): OpenApiOperation
    {
        return $this->operation;
    }
}