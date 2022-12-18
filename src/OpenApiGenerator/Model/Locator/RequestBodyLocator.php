<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\RequestBody;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocatorTest
 */
final class RequestBodyLocator
{
    private MediaTypeLocator $mediaTypeLocator;

    public function __construct()
    {
        $this->mediaTypeLocator = new MediaTypeLocator();
    }

    /**
     * @return array<string, Model>
     */
    public function getModels(string $baseName, RequestBody $requestBody): array
    {
        return $this->mediaTypeLocator->getModels($baseName . ' RequestBody', $requestBody->content);
    }
}
