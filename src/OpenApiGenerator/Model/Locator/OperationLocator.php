<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_merge;
use function assert;
use function is_numeric;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\OperationLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class OperationLocator
{
    private ParameterLocator $parameterLocator;
    private RequestBodyLocator $requestBodyLocator;
    private ResponseLocator $responseLocator;

    public function __construct()
    {
        $this->parameterLocator   = new ParameterLocator();
        $this->requestBodyLocator = new RequestBodyLocator();
        $this->responseLocator    = new ResponseLocator();
    }

    /**
     * @return array<string, NamedSchema>
     */
    public function getNamedSchemas(string $baseName, Operation $operation): array
    {
        $models = [];
        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof Reference) {
                throw ModelException::unresolvedReference($parameter);
            }

            $models = array_merge($models, $this->parameterLocator->getNamedSchemas($baseName, $parameter));
        }

        if ($operation->requestBody instanceof Reference) {
            throw ModelException::unresolvedReference($operation->requestBody);
        }
        if ($operation->requestBody instanceof RequestBody) {
            $models = array_merge($models, $this->requestBodyLocator->getNamedSchemas($baseName, $operation->requestBody));
        }

        if ($operation->responses instanceof Responses) {
            foreach ($operation->responses->getResponses() as $code => $response) {
                if ($response instanceof Reference) {
                    throw ModelException::unresolvedReference($response);
                }

                // The docblock says it can be null, but normally that'll throw TypeErrorException in the constructor.
                // The only way to do it is to manually `Responses::addResponse($status, null)`, which psalm / your IDE
                // should flag anyway...
                assert($response instanceof Response);

                if (is_numeric($code)) {
                    $name = $baseName . ' Status' . $code;
                } else {
                    $name = $baseName . ' ' . $code;
                }
                $models = array_merge($models, $this->responseLocator->getNamedSchemas($name, $response));
            }
        }

        return $models;
    }
}
