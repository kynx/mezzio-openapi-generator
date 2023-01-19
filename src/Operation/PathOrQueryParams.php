<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\PathOrQueryParamsTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class PathOrQueryParams
{
    public function __construct(private readonly string $template, private readonly ClassModel $model)
    {
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getModel(): ClassModel
    {
        return $this->model;
    }
}
