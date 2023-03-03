<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_merge;
use function is_numeric;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\OperationLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class OperationLocator
{
    public function __construct(
        private readonly ParameterLocator $parameterLocator = new ParameterLocator(),
        private readonly RequestBodyLocator $requestBodyLocator = new RequestBodyLocator(),
        private readonly ResponseLocator $responseLocator = new ResponseLocator()
    ) {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSpecifications(string $baseName, Operation $operation): array
    {
        $models = [];
        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof Reference) {
                throw ModelException::unresolvedReference($parameter);
            }

            $name = ModelUtil::getComponentName('parameters', $parameter) ?? $baseName;

            $models = array_merge($models, $this->parameterLocator->getNamedSpecifications($name, $parameter));
        }

        if ($operation->requestBody instanceof Reference) {
            throw ModelException::unresolvedReference($operation->requestBody);
        }
        if ($operation->requestBody instanceof RequestBody) {
            $models = array_merge(
                $models,
                $this->requestBodyLocator->getNamedSchemas($baseName, $operation->requestBody)
            );
        }

        if ($operation->responses instanceof Responses) {
            foreach ($operation->responses->getResponses() as $code => $response) {
                if ($response instanceof Reference) {
                    throw ModelException::unresolvedReference($response);
                }
                if (! $response instanceof Response) {
                    throw ModelException::invalidSchemaItem('response', $operation->responses);
                }

                if (is_numeric($code)) {
                    $name = $baseName . ' Status' . $code;
                } else {
                    $name = $baseName . ' ' . $code;
                }

                $name = ModelUtil::getComponentName('responses', $response) ?? $name;

                $models = array_merge($models, $this->responseLocator->getNamedSchemas($name, $response));
            }
        }

        return $models;
    }
}
