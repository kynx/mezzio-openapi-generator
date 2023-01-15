<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;

/**
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorModel
{
    public function __construct(private readonly string $className, private readonly ClassModel $model)
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getModel(): ClassModel
    {
        return $this->model;
    }
}
