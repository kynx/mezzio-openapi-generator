<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilder
 */
final class RequestBodyBuilderTest extends TestCase
{
    use OperationTrait;

    private RequestBodyBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getRequestBodyBuilder();
    }

    public function testGetRequestBodyReturnsEmpty(): void
    {
        $operation = $this->getOperation('put', []);
        $actual    = $this->builder->getRequestBodyModels($operation, []);
        self::assertSame([], $actual);
    }

    public function testGetRequestBodyReturnsModels(): void
    {
        $expected  = [
            new RequestBodyModel(
                'text/csv',
                new SimpleProperty(
                    '',
                    '',
                    new PropertyMetadata(required: true),
                    PropertyType::String
                )
            ),
            new RequestBodyModel(
                'default',
                new SimpleProperty(
                    '',
                    '',
                    new PropertyMetadata(required: true),
                    PropertyType::String
                )
            ),
        ];
        $operation = $this->getOperation('patch', [
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

        $actual = $this->builder->getRequestBodyModels($operation, []);
        self::assertEquals($expected, $actual);
    }
}
