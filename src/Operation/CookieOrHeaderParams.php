<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParamsTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class CookieOrHeaderParams
{
    public function __construct(private readonly array $templates, private readonly ClassModel $model)
    {
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getModel(): ClassModel
    {
        return $this->model;
    }
}
