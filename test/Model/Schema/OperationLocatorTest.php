<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 */
final class OperationLocatorTest extends TestCase
{
    private OperationLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new OperationLocator();
    }

    public function testGetNamedSchemasReferencedParameterThrowsException(): void
    {
        $ref       = '#/components/parameters/Pet';
        $operation = new Operation([
            'parameters' => [
                new Reference(['$ref' => $ref]),
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSpecifications('', $operation);
    }

    public function testGetNamedSchemasReturnsParameterSchema(): void
    {
        $schema    = $this->getSchema();
        $operation = $this->getOperation([
            'parameters' => [
                [
                    'name'   => 'id',
                    'in'     => 'query',
                    'schema' => $schema,
                ],
            ],
            'responses'  => [
                '200' => [
                    'description' => 'Pet description',
                    'content'     => [
                        'application/json' => [],
                    ],
                ],
            ],
        ]);
        $expected  = [
            '/paths/foo/get/parameters/0/schema' => new NamedSpecification("Path Foo id", $schema),
        ];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSpecifications('Path Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedRequestBodyThrowsException(): void
    {
        $ref       = '#/components/requestBodies/Foo';
        $operation = $this->getOperation([
            'requestBody' => new Reference(['$ref' => $ref]),
            'responses'   => [
                '200' => [
                    'description' => 'Pet description',
                    'content'     => [
                        'application/json' => [],
                    ],
                ],
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSpecifications('', $operation);
    }

    public function testGetNamedSchemasReturnsRequestBody(): void
    {
        $schema    = $this->getSchema();
        $operation = $this->getOperation([
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => $schema,
                    ],
                ],
            ],
            'responses'   => [
                '200' => [
                    'description' => 'Pet description',
                    'content'     => [
                        'application/json' => [],
                    ],
                ],
            ],
        ]);
        $path      = '/paths/foo/get/requestBody/content/application~1json/schema';
        $expected  = [
            $path => new NamedSpecification("Foo RequestBody", $schema),
        ];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedResponseThrowsException(): void
    {
        $ref       = '#/components/responses/Foo';
        $operation = $this->getOperation([
            'responses' => [
                '200' => new Reference(['$ref' => $ref]),
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSpecifications('', $operation);
    }

    public function testGetNamedSchemasReturnsResponse(): void
    {
        $schema    = $this->getSchema();
        $operation = $this->getOperation([
            'responses' => [
                '200' => [
                    'description' => 'Foo description',
                    'content'     => [
                        'application/json' => [
                            'schema' => $schema,
                        ],
                    ],
                ],
            ],
        ]);
        $path      = '/paths/foo/get/responses/200/content/application~1json/schema';
        $expected  = [
            $path => new NamedSpecification("Foo Status200Response", $schema),
        ];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasUsesDefaultStatusForName(): void
    {
        $schema    = $this->getSchema();
        $operation = $this->getOperation([
            'responses' => [
                'default' => [
                    'description' => 'Foo description',
                    'content'     => [
                        'application/json' => [
                            'schema' => $schema,
                        ],
                    ],
                ],
            ],
        ]);
        $path      = '/paths/foo/get/responses/default/content/application~1json/schema';
        $expected  = [
            $path => new NamedSpecification("Foo defaultResponse", $schema),
        ];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    private function getOperation(array $spec, string $pointer = '/paths/foo/get'): Operation
    {
        $operation = new Operation($spec);
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        return $operation;
    }

    private function getSchema(): Schema
    {
        return new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }
}
