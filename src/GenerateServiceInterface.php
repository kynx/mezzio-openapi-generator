<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;

interface GenerateServiceInterface
{
    public function getModels(OpenApi $openApi): ModelCollection;

    public function createModels(ModelCollection $collection): void;

    public function createHydrators(ModelCollection $collection): void;
}
