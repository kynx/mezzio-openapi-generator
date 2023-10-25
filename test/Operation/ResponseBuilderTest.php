<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseHeader;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilder
 */
final class ResponseBuilderTest extends TestCase
{
    use OperationTrait;

    private ResponseBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getResponseBuilder();
    }

    public function testGetResponseModelsReturnsModels(): void
    {
        $expected   = [
            new ResponseModel(
                '200',
                'Pet details',
                'application/json',
                new SimpleProperty(
                    '',
                    '',
                    new PropertyMetadata(required: true),
                    new ClassString('Api\Models\Foo')
                )
            ),
            new ResponseModel(
                '404',
                'Not found',
                null,
                null
            ),
        ];
        $operation  = $this->getOperation('get', [
            '200' => [
                'description' => 'Pet details',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ],
            '404' => [
                'description' => 'Not found',
            ],
        ]);
        $classNames = [
            '/paths/foo/get/responses/200/content/application~1json/schema' => 'Api\Models\Foo',
        ];

        $actual = $this->builder->getResponseModels($operation, $classNames);
        self::assertEquals($expected, $actual);
    }

    public function testGetResponseModelsAddsHeaderWithTemplate(): void
    {
        $expected  = [
            new ResponseModel(
                '201',
                'Created',
                null,
                null,
                [
                    new ResponseHeader(
                        'location',
                        '{location}',
                        null,
                        new SimpleProperty(
                            'location',
                            'location',
                            new PropertyMetadata(),
                            PropertyType::String
                        )
                    ),
                ],
            ),
        ];
        $operation = $this->getOperation('put', [
            '201' => [
                'description' => 'Created',
                'headers'     => [
                    'location' => [
                        'style'  => 'simple',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getResponseModels($operation, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetResponseModelsReturnsHeaderWithMimeType(): void
    {
        $expected  = [
            new ResponseModel(
                '201',
                'Created',
                null,
                null,
                [
                    new ResponseHeader(
                        'X-Foo',
                        null,
                        'application/json',
                        new SimpleProperty(
                            'X-Foo',
                            'X-Foo',
                            new PropertyMetadata(),
                            PropertyType::String
                        )
                    ),
                ],
            ),
        ];
        $operation = $this->getOperation('put', [
            '201' => [
                'description' => 'Created',
                'headers'     => [
                    'X-Foo' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getResponseModels($operation, []);
        self::assertEquals($expected, $actual);
    }

    protected static function getOperation(string $method, array $spec): Operation
    {
        $operation = new Operation(['responses' => $spec]);
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/foo/' . $method));
        self::assertTrue($operation->validate(), implode("\n", $operation->getErrors()));

        return $operation;
    }
}
