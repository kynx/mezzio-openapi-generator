<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator
 */
final class ResponseLocatorTest extends TestCase
{
    private ResponseLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new ResponseLocator();
    }

    public function testGetNamedSchemasReturnsContentSchema(): void
    {
        $schema   = $this->getSchema();
        $response = new Response([
            'description' => 'Foo response',
            'content'     => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ]);
        $expected = ['' => new NamedSchema('FooResponse', $schema)];

        self::assertTrue($response->validate(), implode("\n", $response->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $response);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedHeaderThrowsException(): void
    {
        $ref      = '#/components/headers/Foo';
        $response = new Response([
            'description' => 'Foo response',
            'content'     => [
                'application/json' => [],
            ],
            'headers'     => [
                'foo' => new Reference(['$ref' => $ref]),
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSchemas('', $response);
    }

    public function testGetNamedSchemasReferencedHeaderSchemaThrowsException(): void
    {
        $ref      = '#/components/headers/Foo';
        $response = new Response([
            'description' => 'Foo response',
            'content'     => [
                'application/json' => [],
            ],
            'headers'     => [
                'foo' => [
                    'schema' => new Reference(['$ref' => $ref]),
                ],
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSchemas('', $response);
    }

    public function testGetNamedSchemasSkipsNullSpecification(): void
    {
        $response = new Response([
            'description' => 'Foo response',
            'content'     => [
                'application/json' => [],
            ],
            'headers'     => [
                'foo' => [
                    'schema' => null,
                ],
            ],
        ]);

        self::assertTrue($response->validate(), implode("\n", $response->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $response);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasNormalisesHeaderName(): void
    {
        $schema   = $this->getSchema();
        $response = new Response([
            'description' => 'Foo response',
            'content'     => [
                'application/json' => [],
            ],
            'headers'     => [
                'SET-cookie' => [
                    'schema' => $schema,
                ],
            ],
        ]);
        $expected = ['' => new NamedSchema('FooSetCookieHeader', $schema)];

        self::assertTrue($response->validate(), implode("\n", $response->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $response);
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
