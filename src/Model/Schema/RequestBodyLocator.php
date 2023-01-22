<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\RequestBody;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class RequestBodyLocator
{
    public function __construct(private readonly MediaTypeLocator $mediaTypeLocator = new MediaTypeLocator())
    {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSchemas(string $baseName, RequestBody $requestBody): array
    {
        $name = ModelUtil::getComponentName('requestBodies', $requestBody) ?? "$baseName RequestBody";
        return $this->mediaTypeLocator->getNamedSpecifications($name, $requestBody->content);
    }
}
