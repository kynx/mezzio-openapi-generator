<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Responses;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_merge;
use function is_numeric;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\OperationLocatorTest
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
     * @return array<string, Model>
     */
    public function getModels(string $baseName, Operation $operation): array
    {
        $models = [];
        foreach ($operation->parameters as $parameter) {
            if ($parameter instanceof Reference) {
                throw ModelException::unresolvedReference($parameter);
            }

            $models = array_merge($models, $this->parameterLocator->getModels($baseName, $parameter));
        }

        if ($operation->requestBody instanceof Reference) {
            throw ModelException::unresolvedReference($operation->requestBody);
        }
        if ($operation->requestBody instanceof RequestBody) {
            $models = array_merge($models, $this->requestBodyLocator->getModels($baseName, $operation->requestBody));
        }

        if ($operation->responses instanceof Responses) {
            foreach ($operation->responses->getResponses() as $code => $response) {
                if ($response instanceof Reference) {
                    throw ModelException::unresolvedReference($response);
                }
                if ($response === null) {
                    continue;
                }

                if (is_numeric($code)) {
                    $name = $baseName . ' Status' . $code;
                } else {
                    $name = $baseName . ' ' . $code;
                }
                $models = array_merge($models, $this->responseLocator->getModels($name, $response));
            }
        }

        return $models;
    }
}
