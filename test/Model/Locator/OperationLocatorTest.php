<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OperationLocator
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
        $this->locator->getNamedSchemas('', $operation);
    }

    public function testGetNamedSchemasReturnsEmpty(): void
    {
        $operation = new Operation([
            'parameters' => [
                [
                    'name'   => 'id',
                    'in'     => 'query',
                    'schema' => [
                        'type' => 'string',
                    ],
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

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSchemas('', $operation);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasReturnsParameterSchema(): void
    {
        $schema    = $this->getSchema();
        $operation = new Operation([
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
        $expected  = ['' => new NamedSchema("Foo idParam", $schema)];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSchemas('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedRequestBodyThrowsException(): void
    {
        $ref       = '#/components/requestBodies/Foo';
        $operation = new Operation([
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
        $this->locator->getNamedSchemas('', $operation);
    }

    public function testGetNamedSchemasReturnsRequestBody(): void
    {
        $schema    = $this->getSchema();
        $operation = new Operation([
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
        $expected  = ['' => new NamedSchema("Foo RequestBody", $schema)];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSchemas('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedResponseThrowsException(): void
    {
        $ref       = '#/components/responses/Foo';
        $operation = new Operation([
            'responses' => [
                '200' => new Reference(['$ref' => $ref]),
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSchemas('', $operation);
    }

    public function testGetNamedSchemasReturnsResponse(): void
    {
        $schema    = $this->getSchema();
        $operation = new Operation([
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
        $expected  = ['' => new NamedSchema("Foo Status200Response", $schema)];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSchemas('Foo', $operation);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasUsesDefaultStatusForName(): void
    {
        $schema    = $this->getSchema();
        $operation = new Operation([
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
        $expected  = ['' => new NamedSchema("Foo defaultResponse", $schema)];

        self::assertTrue($operation->validate());
        $actual = $this->locator->getNamedSchemas('Foo', $operation);
        self::assertEquals($expected, $actual);
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