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
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
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
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function implode;
use function ucfirst;

trait OperationTrait
{
    protected static function getNamedSpecification(string $method, array $spec): NamedSpecification
    {
        $operation = self::getOperation($method, $spec);

        $name = ucfirst($method) . 'Operation';
        return new NamedSpecification($name, $operation);
    }

    protected static function getOperation(string $method, array $spec): Operation
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

    protected static function getPropertiesBuilder(): PropertiesBuilder
    {
        return new PropertiesBuilder(self::getUniquePropertyLabeler(), self::getPropertyBuilder());
    }

    protected static function getOperationCollectionBuilder(string $namespace): OperationCollectionBuilder
    {
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Operation'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);

        return new OperationCollectionBuilder($classNamer, self::getOperationBuilder());
    }

    protected static function getOperationBuilder(): OperationBuilder
    {
        return new OperationBuilder(
            self::getParameterBuilder(),
            self::getRequestBodyBuilder(),
            self::getResponseBuilder()
        );
    }

    protected static function getParameterBuilder(): ParameterBuilder
    {
        return new ParameterBuilder(self::getUniquePropertyLabeler(), self::getPropertyBuilder());
    }

    protected static function getPropertyBuilder(): PropertyBuilder
    {
        return new PropertyBuilder(self::getTypeMapper());
    }

    protected static function getRequestBodyBuilder(): RequestBodyBuilder
    {
        return new RequestBodyBuilder(self::getPropertyBuilder());
    }

    protected static function getResponseBuilder(): ResponseBuilder
    {
        return new ResponseBuilder(self::getPropertyBuilder());
    }

    protected static function getUniquePropertyLabeler(): UniqueVariableLabeler
    {
        return new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
    }

    protected static function getTypeMapper(): TypeMapper
    {
        return new TypeMapper(new DateTimeImmutableMapper());
    }

    protected static function getPathParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/path'
    ): PathOrQueryParams {
        return new PathOrQueryParams(
            '{foo}',
            new ClassModel($namespace . '\\PathParams', $pointer, [], self::getSimpleProperty('foo'))
        );
    }

    protected static function getQueryParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/query'
    ): PathOrQueryParams {
        return new PathOrQueryParams(
            '{?bar}',
            new ClassModel($namespace . '\\QueryParams', $pointer, [], self::getSimpleProperty('bar'))
        );
    }

    protected static function getHeaderParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/header'
    ): CookieOrHeaderParams {
        return new CookieOrHeaderParams(
            ['X-Foo' => '{X-Foo}'],
            new ClassModel($namespace . '\\HeaderParams', $pointer, [], self::getSimpleProperty('xFoo', 'X-Foo'))
        );
    }

    protected static function getCookieParams(
        string $namespace = '',
        string $pointer = '/paths/{foo}/get/parameters/cookie'
    ): CookieOrHeaderParams {
        return new CookieOrHeaderParams(
            ['cook' => '{cook}'],
            new ClassModel($namespace . '\\CookieParams', $pointer, [], self::getSimpleProperty('cook'))
        );
    }

    /**
     * @return list<RequestBodyModel>
     */
    protected static function getRequestBodies(): array
    {
        return [
            new RequestBodyModel(
                'text/plain',
                new SimpleProperty('', '', new PropertyMetadata(required: true), PropertyType::String)
            ),
        ];
    }

    protected static function getResponse(): ResponseModel
    {
        $property = new SimpleProperty('', '', new PropertyMetadata(required: true), PropertyType::String);
        return new ResponseModel('default', 'Hello world', 'text/plain', $property);
    }

    protected static function getSimpleProperty(string $name, string|null $originalName = null): SimpleProperty
    {
        $originalName = $originalName ?? $name;
        return new SimpleProperty(
            '$' . $name,
            $originalName,
            new PropertyMetadata(required: true),
            PropertyType::String
        );
    }

    /**
     * @param list<OperationModel> $operations
     */
    protected static function getOperationCollection(array $operations): OperationCollection
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
    protected static function getOperations(string $namespace = 'Api\\Operation'): array
    {
        return [
            new OperationModel($namespace . '\\Foo\\Get\\Operation', '/paths/~1foo/get', self::getPathParams()),
            new OperationModel($namespace . '\\Bar\\Get\\Operation', '/paths/~1bar/get', null, self::getQueryParams()),
        ];
    }
}
