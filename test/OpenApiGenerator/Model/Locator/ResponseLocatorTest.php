<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
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

    public function testGetModelsReturnsContentSchema(): void
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
        $expected = ['' => new Model('FooResponse', $schema)];

        self::assertTrue($response->validate(), implode("\n", $response->getErrors()));
        $actual = $this->locator->getModels('Foo', $response);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReferencedHeaderThrowsException(): void
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
        $this->locator->getModels('', $response);
    }

    public function testGetModelsReferencedHeaderSchemaThrowsException(): void
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
        $this->locator->getModels('', $response);
    }

    public function testGetModelsSkipsNullSpecification(): void
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
        $actual = $this->locator->getModels('Foo', $response);
        self::assertEmpty($actual);
    }

    public function testGetModelsNormalisesHeaderName(): void
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
        $expected = ['' => new Model('FooSetCookieHeader', $schema)];

        self::assertTrue($response->validate(), implode("\n", $response->getErrors()));
        $actual = $this->locator->getModels('Foo', $response);
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
