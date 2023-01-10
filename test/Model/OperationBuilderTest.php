<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

use function implode;
use function ucfirst;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\OperationModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder
 */
final class OperationBuilderTest extends TestCase
{
    private OperationBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $propertyLabeler = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());

        $this->builder = new OperationBuilder($propertyLabeler);
    }

    public function testGetModelsReturnsEmpty(): void
    {
        $expected  = [];
        $pointer   = '/paths/foo/get';
        $namedSpec = $this->getNamedSpecification('get', []);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider addParameterProvider
     */
    public function testGetModelsAddsParams(string $in): void
    {
        $section       = ucfirst($in);
        $pointer       = '/paths/foo/get';
        $className     = '\\FooOperation';
        $propertyClass = '\\FooOperation\\' . $section . 'Params';
        $fooProperty   = new SimpleProperty(
            '$foo',
            'foo',
            new PropertyMetadata(...['required' => true]),
            PropertyType::String
        );
        $pathProperty  = new SimpleProperty(
            '$' . $in . 'Params',
            '',
            new PropertyMetadata(...['required' => true]),
            $propertyClass
        );
        $expected      = [
            new ClassModel($propertyClass, $pointer . '/parameters/' . $in, [], $fooProperty),
            new OperationModel($className, $pointer, $pathProperty),
        ];
        $namedSpec     = $this->getNamedSpecification('get', spec: [
            'parameters' => [
                [
                    'name'     => 'foo',
                    'in'       => $in,
                    'required' => true,
                    'schema'   => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertEquals($expected, $actual);
    }

    public function addParameterProvider(): array
    {
        return [
            'path'   => ['path'],
            'query'  => ['query'],
            'header' => ['header'],
            'cookie' => ['cookie'],
        ];
    }

    public function testGetModelsReturnsParamWithContent(): void
    {
        $pointer       = '/paths/foo/get';
        $className     = '\\FooOperation';
        $propertyClass = '\\FooOperation\\QueryParams';
        $fooProperty   = new SimpleProperty(
            '$foo',
            'foo',
            new PropertyMetadata(...['required' => true]),
            '\\Bar'
        );
        $pathProperty  = new SimpleProperty(
            '$queryParams',
            '',
            new PropertyMetadata(...['required' => true]),
            $propertyClass
        );
        $expected      = [
            new ClassModel($propertyClass, $pointer . '/parameters/query', [], $fooProperty),
            new OperationModel($className, $pointer, $pathProperty),
        ];
        $namedSpec     = $this->getNamedSpecification('get', spec: [
            'parameters' => [
                [
                    'name'     => 'foo',
                    'in'       => 'query',
                    'required' => true,
                    'content'  => [
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'bar' => [
                                        "type" => "string",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $classMap      = [
            $pointer => '\\FooOperation',
            $pointer . '/parameters/0/content/application~1json/schema' => '\\Bar',
        ];

        $actual = $this->builder->getModels($namedSpec, $classMap);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsRequestBodyWithUniqueType(): void
    {
        $pointer     = '/paths/foo/patch';
        $className   = '\\FooOperation';
        $requestBody = new SimpleProperty(
            '$requestBody',
            '',
            new PropertyMetadata(...['required' => true]),
            PropertyType::String
        );
        $expected    = [
            new OperationModel($className, $pointer, $requestBody),
        ];
        $namedSpec   = $this->getNamedSpecification('patch', spec: [
            'requestBody' => [
                'required' => true,
                'content'  => [
                    'text/csv' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                    'default'  => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsRequestBodyWithUnionType(): void
    {
        $pointer     = '/paths/foo/patch';
        $className   = '\\FooOperation';
        $requestBody = new UnionProperty(
            '$requestBody',
            '',
            new PropertyMetadata(),
            PropertyType::String,
            PropertyType::Integer
        );
        $expected    = [
            new OperationModel($className, $pointer, $requestBody),
        ];
        $namedSpec   = $this->getNamedSpecification('patch', spec: [
            'requestBody' => [
                'content' => [
                    'default' => [
                        'schema' => [
                            'enum' => ['true', 'false', 1, 0],
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsRequestBodyWithArrayProperty(): void
    {
        $pointer     = '/paths/foo/patch';
        $className   = '\\FooOperation';
        $requestBody = new ArrayProperty(
            '$requestBody',
            '',
            new PropertyMetadata(),
            true,
            PropertyType::String
        );
        $expected    = [
            new OperationModel($className, $pointer, $requestBody),
        ];
        $namedSpec   = $this->getNamedSpecification('patch', spec: [
            'requestBody' => [
                'content' => [
                    'default' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsRequestBodyWithArrayTypeAdded(): void
    {
        $pointer     = '/paths/foo/patch';
        $className   = '\\FooOperation';
        $requestBody = new UnionProperty(
            '$requestBody',
            '',
            new PropertyMetadata(),
            PropertyType::String,
            PropertyType::Array
        );
        $expected    = [
            new OperationModel($className, $pointer, $requestBody),
        ];
        $namedSpec   = $this->getNamedSpecification('patch', spec: [
            'requestBody' => [
                'content' => [
                    'text/csv' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                    'default'  => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getModels($namedSpec, [$pointer => '\\FooOperation']);
        self::assertEquals($expected, $actual);
    }

    private function getNamedSpecification(string $method, array $spec): NamedSpecification
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
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/foo/' . $method));
        self::assertTrue($operation->validate(), implode("\n", $operation->getErrors()));

        $name = ucfirst($method) . 'Operation';
        return new NamedSpecification($name, $operation);
    }
}
