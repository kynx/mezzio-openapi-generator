<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator
 */
final class SchemaLocatorTest extends TestCase
{
    private SchemaLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new SchemaLocator();
    }

    public function testGetModelsReturnsEmptyForScalarSchema(): void
    {
        $schema = new Schema([
            'type' => 'integer',
        ]);
        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEmpty($actual);
    }

    public function testGetModelsReferencedSchemaUsesPointerName(): void
    {
        $schema  = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $pointer = '/components/schemas/Foo';
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $expected = [$pointer => new NamedSchema('Foo', $schema)];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Bar', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsArrayItemModel(): void
    {
        $itemSchema = [
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ];
        $schema     = new Schema([
            'type'  => 'array',
            'items' => $itemSchema,
        ]);
        $expected   = ['' => new NamedSchema('FooItem', new Schema($itemSchema))];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsAdditionalPropertiesModel(): void
    {
        $itemSchema = [
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ];
        $schema     = new Schema([
            'type'                 => 'object',
            'additionalProperties' => $itemSchema,
        ]);
        $expected   = ['' => new NamedSchema('FooItem', new Schema($itemSchema))];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsEnumModel(): void
    {
        $schema   = new Schema([
            'type' => 'string',
            'enum' => ['cat', 'dog', 'lizard'],
        ]);
        $expected = ['' => new NamedSchema('Foo', $schema)];

        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsAllOfModel(): void
    {
        $pointer = '/components/schemas/Foo';
        $first   = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $second  = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $schema  = new Schema([
            'allOf' => [$first, $second],
        ]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $expected = [
            $pointer => new NamedSchema('Foo', $schema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsReferencedAllOfModels(): void
    {
        $pointer      = '/components/schemas/Foo';
        $first        = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $firstPointer = '/components/schemas/Bar';
        $second       = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $schema       = new Schema([
            'allOf' => [$first, $second],
        ]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $first->setDocumentContext(new OpenApi([]), new JsonPointer($firstPointer));
        $expected = [
            $pointer      => new NamedSchema('Foo', $schema),
            $firstPointer => new NamedSchema('Bar', $first),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsAnyOfModel(): void
    {
        $pointer      = '/components/schemas/Foo';
        $first        = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $firstPointer = '/components/schemas/Bar';
        $second       = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $schema       = new Schema([
            'anyOf' => [$first, $second],
        ]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $first->setDocumentContext(new OpenApi([]), new JsonPointer($firstPointer));
        $expected = [
            $pointer => new NamedSchema('Foo', $schema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsSubsequentlyReferencedAnyOfModels(): void
    {
        $pointer      = '/components/schemas/Foo';
        $first        = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $firstPointer = '/components/schemas/Bar';
        $second       = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $anyOf        = new Schema([
            'anyOf' => [$first, $second],
        ]);
        $schema       = new Schema([
            'type'       => 'object',
            'properties' => [
                'anyOf'      => $anyOf,
                'referenced' => $first,
            ],
        ]);
        $anyOf->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $first->setDocumentContext(new OpenApi([]), new JsonPointer($firstPointer));
        $expected = [
            $pointer      => new NamedSchema('Foo', $anyOf),
            $firstPointer => new NamedSchema('Bar', $first),
            ''            => new NamedSchema('Baz', $schema),
        ];

        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        $actual = $this->locator->getModels('Baz', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsOneOfModels(): void
    {
        $pointer = '/components/schemas/Foo';
        $first   = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $second  = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $schema  = new Schema([
            'oneOf' => [$first, $second],
        ]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $expected = [
            $pointer . '/oneOf/0' => new NamedSchema("Foo0", $first),
            $pointer . '/oneOf/1' => new NamedSchema("Foo1", $second),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsRecursesProperties(): void
    {
        $pointer        = '/components/schemas/Foo';
        $propertySchema = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $schema         = new Schema([
            'type'       => 'object',
            'properties' => [
                'pet' => $propertySchema,
            ],
        ]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $expected = [
            $pointer                     => new NamedSchema('Foo', $schema),
            $pointer . '/properties/pet' => new NamedSchema('Foo pet', $propertySchema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getModels('Foo', $schema);
        self::assertEquals($expected, $actual);
    }
}
