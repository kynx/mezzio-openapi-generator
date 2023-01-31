<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder
 */
final class OperationBuilderTest extends TestCase
{
    use OperationTrait;

    private OperationBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $propertyLabeler = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
        $this->builder   = new OperationBuilder(new ParameterBuilder($propertyLabeler));
    }

    public function testGetModelsReturnsEmptyModel(): void
    {
        $className = '\\Operation';
        $pointer   = '/paths/{foo}/get';
        $expected  = new OperationModel($className, $pointer);
        $namedSpec = $this->getNamedSpecification('get', []);

        $actual = $this->builder->getOperationModel($namedSpec, [$pointer => $className]);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider addParameterProvider
     */
    public function testGetModelsAddsParams(string $in, string $name, OperationModel $expected): void
    {
        $namedSpec = $this->getNamedSpecification('get', spec: [
            'parameters' => [
                [
                    'name'     => $name,
                    'in'       => $in,
                    'required' => true,
                    'explode'  => false,
                    'schema'   => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getOperationModel($namedSpec, ['/paths/{foo}/get' => '\\Operation']);
        self::assertEquals($expected, $actual);
    }

    public function addParameterProvider(): array
    {
        $class   = '\\Operation';
        $pointer = '/paths/{foo}/get';

        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'path'   => ['path', 'foo', new OperationModel($class, $pointer, $this->getPathParams())],
            'query'  => ['query', 'bar', new OperationModel($class, $pointer, null, $this->getQueryParams())],
            'header' => ['header', 'X-Foo', new OperationModel($class, $pointer, null, null, $this->getHeaderParams())],
            'cookie' => ['cookie', 'cook', new OperationModel($class, $pointer, null, null, null, $this->getCookieParams())],
        ];
        // phpcs:enable
    }

    public function testGetOperationModelAddsRequestBodies(): void
    {
        $class    = '\\Operation';
        $pointer  = '/paths/{foo}/get';
        $expected = new OperationModel($class, $pointer, null, null, null, null, $this->getRequestBodies());

        $namedSpec = $this->getNamedSpecification('get', spec: [
            'requestBody' => [
                'required' => true,
                'content'  => [
                    'text/plain' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getOperationModel($namedSpec, ['/paths/{foo}/get' => '\\Operation']);
        self::assertEquals($expected, $actual);
    }
}
