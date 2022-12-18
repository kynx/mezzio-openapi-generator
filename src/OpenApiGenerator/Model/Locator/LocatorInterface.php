<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\OpenApi;

interface LocatorInterface
{
    /**
     * Returns list of models found in specification
     *
     * @return list<Model>
     */
    public function getModels(OpenApi $openApi): array;
}
