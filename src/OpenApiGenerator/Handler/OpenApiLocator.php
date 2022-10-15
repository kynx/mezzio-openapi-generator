<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApi\OpenApiRouteParameter;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApi\ParameterStyle;
use Kynx\Mezzio\OpenApi\SchemaType;

use function array_filter;
use function array_map;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\OpenApiLocatorTest
 */
final class OpenApiLocator implements HandlerLocatorInterface
{
    public function __construct(private OpenApi $openApi, private HandlerNamerInterface $namer)
    {
    }

    public function create(): HandlerCollection
    {
        $collection = new HandlerCollection();

        foreach ($this->getHandlerClasses() as $handlerFile) {
            $collection->add($handlerFile);
        }

        return $collection;
    }

    private function getHandlerClasses(): array
    {
        $operations = [];
        foreach ($this->openApi->paths as $path => $spec) {
            foreach ($spec->getOperations() as $method => $operation) {
                $operations[] = new OpenApiOperation(
                    $operation->operationId,
                    $path,
                    $method,
                    ...$this->getParameters($operation)
                );
            }
        }

        $handlerClasses = [];
        foreach ($this->namer->keyByUniqueName($operations) as $className => $operation) {
            $handlerClasses[] = new HandlerClass($className, $operation);
        }

        return $handlerClasses;
    }

    private function getParameters(Operation $operation): array
    {
        $pathParams = array_filter($operation->parameters, fn(Parameter $param): bool => $param->in === 'path');

        return array_map(fn (Parameter $param): OpenApiRouteParameter => $this->getParameter($param), $pathParams);
    }

    private function getParameter(Parameter $parameter): OpenApiRouteParameter
    {
        $schema = $parameter->schema;
        return new OpenApiRouteParameter(
            $parameter->name,
            ParameterStyle::from($parameter->style),
            new OpenApiSchema(SchemaType::from($schema->type), $schema->format)
        );
    }
}
