<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;

use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestBodyBuilder
{
    public function __construct(private readonly PropertyBuilder $propertyBuilder)
    {
    }

    /**
     * @param array<string, string> $classNames
     * @return list<RequestBodyModel>
     */
    public function getRequestBodyModels(Operation $operation, array $classNames): array
    {
        $requestBody = $operation->requestBody;
        if (! $requestBody instanceof RequestBody) {
            return [];
        }

        $requestBodies = [];
        /** @var string $mimeType */
        foreach ($requestBody->content as $mimeType => $mediaType) {
            assert($mediaType->schema instanceof Schema);

            $property        = $this->propertyBuilder->getProperty(
                $mediaType->schema,
                '',
                '',
                true,
                $classNames
            );
            $requestBodies[] = new RequestBodyModel($mimeType, $property);
        }

        return $requestBodies;
    }
}
