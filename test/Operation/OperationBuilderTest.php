<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
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

        $this->builder = $this->getOperationBuilder();
    }

    public function testGetModelsReturnsEmptyModel(): void
    {
        $className = '\\Operation';
        $pointer   = '/paths/{foo}/get';
        $responses = [$this->getResponse()];
        $expected  = new OperationModel($className, $pointer, null, null, null, null, [], $responses);
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
        $class     = '\\Operation';
        $pointer   = '/paths/{foo}/get';
        $responses = [$this->getResponse()];

        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'path'   => ['path', 'foo', new OperationModel($class, $pointer, $this->getPathParams(), null, null, null, [], $responses)],
            'query'  => ['query', 'bar', new OperationModel($class, $pointer, null, $this->getQueryParams(), null, null, [], $responses)],
            'header' => ['header', 'X-Foo', new OperationModel($class, $pointer, null, null, $this->getHeaderParams(), null, [], $responses)],
            'cookie' => ['cookie', 'cook', new OperationModel($class, $pointer, null, null, null, $this->getCookieParams(), [], $responses)],
        ];
        // phpcs:enable
    }

    public function testGetOperationModelAddsRequestBodies(): void
    {
        $class     = '\\Operation';
        $pointer   = '/paths/{foo}/get';
        $responses = [$this->getResponse()];
        $expected  = new OperationModel(
            $class,
            $pointer,
            null,
            null,
            null,
            null,
            $this->getRequestBodies(),
            $responses
        );

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
