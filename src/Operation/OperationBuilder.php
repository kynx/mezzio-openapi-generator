<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_slice;
use function assert;
use function explode;
use function implode;
use function str_replace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OperationBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationBuilder
{
    public function __construct(
        private readonly ParameterBuilder $parameterBuilder,
        private readonly RequestBodyBuilder $requestBodyBuilder = new RequestBodyBuilder(),
        private readonly ResponseBuilder $responseBuilder = new ResponseBuilder()
    ) {
    }

    /**
     * @param array<string, string> $classNames
     */
    public function getOperationModel(NamedSpecification $namedSpec, array $classNames): OperationModel
    {
        $pointer = $namedSpec->getJsonPointer();
        assert(isset($classNames[$pointer]));
        $path = $this->getPath($pointer);

        $className = $classNames[$pointer];
        $namespace = GeneratorUtil::getNamespace($className);
        $operation = $namedSpec->getSpecification();
        assert($operation instanceof Operation);

        $properties = [];
        $positions  = [
            'path'   => $namespace . '\PathParams',
            'query'  => $namespace . '\QueryParams',
            'header' => $namespace . '\HeaderParams',
            'cookie' => $namespace . '\CookieParams',
        ];
        foreach ($positions as $in => $name) {
            $propertyName              = $in . 'Params';
            $properties[$propertyName] = $this->parameterBuilder->getParameterModel(
                $operation,
                $path,
                $name,
                $in,
                $classNames
            );
        }

        $properties['requestBodies'] = $this->requestBodyBuilder->getRequestBodyModels($operation, $classNames);
        $properties['responses']     = $this->responseBuilder->getResponseModels($operation, $classNames);

        $pointer = $operation->getDocumentPosition()?->getPointer() ?? '';
        /** @psalm-suppress PossiblyInvalidArgument Don't get this one :| */
        return new OperationModel($className, $pointer, ...$properties);
    }

    private function getPath(string $pointer): string
    {
        $path = str_replace(['~0', '~1'], ['~', '/'], $pointer);
        return implode('/', array_slice(explode('/', $path), 2, -1));
    }
}
