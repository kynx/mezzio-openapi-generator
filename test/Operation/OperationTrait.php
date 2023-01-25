<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function implode;
use function ucfirst;

trait OperationTrait
{
    protected function getNamedSpecification(string $method, array $spec): NamedSpecification
    {
        $operation = $this->getOperation($method, $spec);

        $name = ucfirst($method) . 'Operation';
        return new NamedSpecification($name, $operation);
    }

    protected function getOperation(string $method, array $spec): Operation
    {
        $spec['responses'] = [
            'default' => [
                'description' => 'Hello world',
                'content'     => [
                    'text/plain' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $operation = new Operation($spec);
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/{foo}/' . $method));
        self::assertTrue($operation->validate(), implode("\n", $operation->getErrors()));

        return $operation;
    }

    protected function getOperationCollectionBuilder(string $namespace): OperationCollectionBuilder
    {
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Operation'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);

        $propertyLabeler = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());

        $parameterBuilder = new ParameterBuilder($propertyLabeler);
        $operationBuilder = new OperationBuilder($parameterBuilder);

        return new OperationCollectionBuilder($classNamer, $operationBuilder);
    }

    protected function getPathParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/path'
    ): PathOrQueryParams {
        return new PathOrQueryParams(
            '{foo}',
            new ClassModel($namespace . '\\PathParams', $pointer, [], $this->getSimpleProperty('foo'))
        );
    }

    protected function getQueryParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/query'
    ): PathOrQueryParams {
        return new PathOrQueryParams(
            '{?bar}',
            new ClassModel($namespace . '\\QueryParams', $pointer, [], $this->getSimpleProperty('bar'))
        );
    }

    protected function getHeaderParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/header'
    ): CookieOrHeaderParams {
        return new CookieOrHeaderParams(
            ['X-Foo' => '{X-Foo}'],
            new ClassModel($namespace . '\\HeaderParams', $pointer, [], $this->getSimpleProperty('xFoo', 'X-Foo'))
        );
    }

    protected function getCookieParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/cookie'
    ): CookieOrHeaderParams {
        return new CookieOrHeaderParams(
            ['cook' => '{cook}'],
            new ClassModel($namespace . '\\CookieParams', $pointer, [], $this->getSimpleProperty('cook'))
        );
    }

    /**
     * @return list<RequestBodyModel>
     */
    protected function getRequestBodies(): array
    {
        return [
            new RequestBodyModel(
                'text/plain',
                new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String)
            ),
        ];
    }

    protected function getSimpleProperty(string $name, string|null $originalName = null): SimpleProperty
    {
        $originalName = $originalName ?? $name;
        return new SimpleProperty(
            '$' . $name,
            $originalName,
            new PropertyMetadata(...['required' => true]),
            PropertyType::String
        );
    }

    /**
     * @param list<OperationModel> $operations
     */
    protected function getOperationCollection(array $operations): OperationCollection
    {
        $collection = new OperationCollection();
        foreach ($operations as $operation) {
            $collection->add($operation);
        }

        return $collection;
    }

    /**
     * @return list<OperationModel>
     */
    protected function getOperations(string $namespace): array
    {
        return [
            new OperationModel(
                $namespace . '\\Foo\\Get\\Operation',
                '/paths/~1foo/get',
                $this->getPathParams(),
                null,
                null,
                null,
                []
            ),
            new OperationModel(
                $namespace . '\\Bar\\Get\\Operation',
                '/paths/~1bar/get',
                null,
                $this->getQueryParams(),
                null,
                null,
                []
            ),
        ];
    }
}
