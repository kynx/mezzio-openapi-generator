<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;

/**
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerModel
{
    public function __construct(
        private readonly string $jsonPointer,
        private readonly string $className,
        private readonly OperationModel|null $operation
    ) {
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getOperation(): ?OperationModel
    {
        return $this->operation;
    }
}
