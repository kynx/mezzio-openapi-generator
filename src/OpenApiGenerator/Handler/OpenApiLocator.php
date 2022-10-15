<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApi\OpenApiRouteParameter;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApi\ParameterStyle;
use Kynx\Mezzio\OpenApi\SchemaType;

use function array_filter;
use function array_map;
use function assert;

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

        foreach ($this->getHandlerClasses() as $handlerClass) {
            $collection->add($handlerClass);
        }

        return $collection;
    }

    /**
     * @return list<HandlerClass>
     */
    private function getHandlerClasses(): array
    {
        $operations = [];
        /** @var string $path */
        foreach ($this->openApi->paths as $path => $spec) {
            /** @var string $method */
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

    /**
     * @return list<OpenApiRouteParameter>
     */
    private function getParameters(Operation $operation): array
    {
        /** @var list<Parameter> $pathParams */
        $pathParams = array_filter($operation->parameters, function (Parameter|Reference $param): bool {
            return $param instanceof Parameter && $param->in === 'path';
        });

        return array_map(fn (Parameter $param): OpenApiRouteParameter => $this->getParameter($param), $pathParams);
    }

    private function getParameter(Parameter $parameter): OpenApiRouteParameter
    {
        $schema = $parameter->schema;
        // @fixme: can path parameters have `content` instead of `schema`?
        assert($schema instanceof Schema);

        return new OpenApiRouteParameter(
            $parameter->name,
            ParameterStyle::from($parameter->style),
            new OpenApiSchema(SchemaType::from($schema->type), $schema->format)
        );
    }
}
