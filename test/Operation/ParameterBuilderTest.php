<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder
 */
final class ParameterBuilderTest extends TestCase
{
    use OperationTrait;

    private ParameterBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getParameterBuilder();
    }

    public function testGetParametersFiltersIn(): void
    {
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'     => 'foo',
                    'in'       => 'path',
                    'required' => true,
                    'schema'   => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getParameterModel($operation, '/paths/foo/get', '\\Foo', 'query', []);
        self::assertNull($actual);
    }

    public function testGetParametersPopulatesModel(): void
    {
        $expected  = new PathOrQueryParams('{?foo*}', new ClassModel(
            '\\Foo',
            '/paths/{foo}/get/parameters/query',
            [],
            new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String),
        ));
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'   => 'foo',
                    'in'     => 'query',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getParameterModel($operation, '/paths/{foo}/get', '\\Foo', 'query', []);
        self::assertEquals($expected, $actual);
    }

    public function testGetParametersCreatesUniqueProperties(): void
    {
        $expected  = [
            '€'    => '$euro1',
            'euro' => '$euro2',
        ];
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'   => '€',
                    'in'     => 'query',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
                [
                    'name'   => 'euro',
                    'in'     => 'query',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $param = $this->builder->getParameterModel($operation, '/paths/foo/get', '\\Foo', 'query', []);
        self::assertInstanceOf(PathOrQueryParams::class, $param);

        self::assertSame('{?€*,euro*}', $param->getTemplate());
        $actual = [];
        foreach ($param->getModel()->getProperties() as $property) {
            $actual[$property->getOriginalName()] = $property->getName();
        }
        self::assertSame($expected, $actual);
    }

    public function testGetParametersUsesSchema(): void
    {
        $schema    = new Schema(['type' => 'string']);
        $expected  = [
            new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String),
        ];
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'   => 'foo',
                    'in'     => 'query',
                    'schema' => $schema,
                ],
            ],
        ]);

        $param = $this->builder->getParameterModel($operation, '/paths/foo/get', '\\Foo', 'query', []);
        self::assertInstanceOf(PathOrQueryParams::class, $param);
        self::assertEquals($expected, $param->getModel()->getProperties());
    }

    public function testGetParametersUsesContent(): void
    {
        $pointer   = '/paths/{foo}/get';
        $expected  = [
            new SimpleProperty('$foo', 'foo', new PropertyMetadata(), new ClassString('\\Bar')),
        ];
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'    => 'foo',
                    'in'      => 'query',
                    'content' => [
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
        $classMap  = [$pointer . '/parameters/0/content/application~1json/schema' => '\\Bar'];

        $param = $this->builder->getParameterModel($operation, '/paths/{foo}/get', '\\Foo', 'query', $classMap);
        self::assertInstanceOf(PathOrQueryParams::class, $param);
        self::assertSame('', $param->getTemplate());
        self::assertEquals($expected, $param->getModel()->getProperties());
    }

    /**
     * @dataProvider paramTemplateProvider
     */
    public function testGetParameterModelFormatsTemplate(
        bool $isPathOrQuery,
        string $in,
        string $style,
        bool $explode,
        array|string $expected
    ): void {
        $operation = $this->getOperation('get', [
            'parameters' => [
                [
                    'name'     => 'foo',
                    'in'       => $in,
                    'style'    => $style,
                    'explode'  => $explode,
                    'required' => true,
                    'schema'   => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $param = $this->builder->getParameterModel($operation, '/{foo}/get', '\\Foo', $in, []);
        if ($isPathOrQuery) {
            self::assertInstanceOf(PathOrQueryParams::class, $param);
            self::assertSame($expected, $param->getTemplate());
        } else {
            self::assertInstanceOf(CookieOrHeaderParams::class, $param);
            self::assertSame($expected, $param->getTemplates());
        }
    }

    /**
     * @return array<string, array{0: bool, 1: string, 2: string, 3: bool, 4: array|string}>
     */
    public static function paramTemplateProvider(): array
    {
        return [
            'cookie'              => [false, 'cookie', 'form', false, ['foo' => '{foo}']],
            'cookie_explode'      => [false, 'cookie', 'form', true, ['foo' => '{foo*}']],
            'header'              => [false, 'header', 'simple', false, ['foo' => '{foo}']],
            'header_explode'      => [false, 'header', 'simple', true, ['foo' => '{foo*}']],
            'path_simple'         => [true, 'path', 'simple', false, '/{foo}/get'],
            'path_simple_explode' => [true, 'path', 'simple', true, '/{foo*}/get'],
            'path_label'          => [true, 'path', 'label', false, '/{.foo}/get'],
            'path_label_explode'  => [true, 'path', 'label', true, '/{.foo*}/get'],
            'path_matrix'         => [true, 'path', 'matrix', false, '/{;foo}/get'],
            'path_matrix_explode' => [true, 'path', 'matrix', true, '/{;foo*}/get'],
            'query_form'          => [true, 'query', 'form', false, '{?foo}'],
            'query_form_explode'  => [true, 'query', 'form', true, '{?foo*}'],
            'query_space'         => [true, 'query', 'spaceDelimited', false, '{?foo_}'],
            'query_space_explode' => [true, 'query', 'spaceDelimited', true, '{?foo*}'],
            'query_pipe'          => [true, 'query', 'pipeDelimited', false, '{?foo|}'],
            'query_pipe_explode'  => [true, 'query', 'pipeDelimited', true, '{?foo*}'],
            'query_deep'          => [true, 'query', 'deepObject', false, '{?foo%}'],
            'query_deep_explode'  => [true, 'query', 'deepObject', true, '{?foo%}'],
        ];
    }
}
