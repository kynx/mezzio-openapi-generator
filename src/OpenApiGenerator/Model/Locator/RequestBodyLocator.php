<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\RequestBody;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class RequestBodyLocator
{
    private MediaTypeLocator $mediaTypeLocator;

    public function __construct()
    {
        $this->mediaTypeLocator = new MediaTypeLocator();
    }

    /**
     * @return array<string, NamedSchema>
     */
    public function getModels(string $baseName, RequestBody $requestBody): array
    {
        return $this->mediaTypeLocator->getModels($baseName . ' RequestBody', $requestBody->content);
    }
}
