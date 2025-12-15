<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(SchemaLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(ModelException::class)]
#[UsesClass(ModelUtil::class)]
final class SchemaLocatorTest extends TestCase
{
    private SchemaLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new SchemaLocator();
    }

    public function testGetNamedSchemasReturnsEmptyForScalarSchema(): void
    {
        $schema = new Schema([
            'type' => 'integer',
        ]);
        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasReferencedSchemaUsesPointerName(): void
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
        $expected = [$pointer => new NamedSpecification('schema Foo', $schema)];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Bar', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsArrayItemSchema(): void
    {
        $item       = [
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ];
        $schema     = new Schema([
            'type'  => 'array',
            'items' => $item,
        ]);
        $itemSchema = new Schema($item);

        $pointer = '/components/schemas/Foo';
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $itemPointer = $pointer . '/items';
        $itemSchema->setDocumentContext(new OpenApi([]), new JsonPointer($itemPointer));

        $expected = [$pointer . '/items' => new NamedSpecification('schema FooItem', $itemSchema)];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsAdditionalPropertiesSchema(): void
    {
        $item       = [
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ];
        $schema     = new Schema([
            'type'                 => 'object',
            'additionalProperties' => $item,
        ]);
        $itemSchema = new Schema($item);

        $pointer = '/components/schemas/Foo';
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $itemPointer = $pointer . '/additionalProperties';
        $itemSchema->setDocumentContext(new OpenApi([]), new JsonPointer($itemPointer));

        $expected = [$itemPointer => new NamedSpecification('schema FooItem', $itemSchema)];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsEnumSchema(): void
    {
        $schema   = new Schema([
            'type' => 'string',
            'enum' => ['cat', 'dog', 'lizard'],
        ]);
        $expected = ['' => new NamedSpecification('Foo', $schema)];

        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsAllOfSchema(): void
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
            $pointer => new NamedSpecification('schema Foo', $schema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsReferencedAllOfSchemas(): void
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
            $pointer      => new NamedSpecification('schema Foo', $schema),
            $firstPointer => new NamedSpecification('schema Bar', $first),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasAllOfReferenceThrowsException(): void
    {
        $pointer   = '/components/schemas/Foo';
        $expected  = "Unresolved reference: '$pointer'";
        $reference = new Reference(['$ref' => $pointer]);
        $schema    = new Schema([
            'allOf' => [$reference],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage($expected);
        $this->locator->getNamedSpecifications('Foo', $schema);
    }

    public function testGetNamedSchemasReturnsAnyOfSchema(): void
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
            $pointer => new NamedSpecification('schema Foo', $schema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsSubsequentlyReferencedAnyOfSchemas(): void
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
            $pointer      => new NamedSpecification('schema Foo', $anyOf),
            $firstPointer => new NamedSpecification('schema Bar', $first),
            ''            => new NamedSpecification('Baz', $schema),
        ];

        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        $actual = $this->locator->getNamedSpecifications('Baz', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedAnyOfThrowsException(): void
    {
        $pointer   = '/components/schemas/Foo';
        $expected  = "Unresolved reference: '$pointer'";
        $reference = new Reference(['$ref' => $pointer]);
        $schema    = new Schema([
            'anyOf' => [$reference],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage($expected);
        $this->locator->getNamedSpecifications('Foo', $schema);
    }

    public function testGetNamedSchemasReturnsOneOfSchemas(): void
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
            $pointer . '/oneOf/0' => new NamedSpecification("schema Foo0", $first),
            $pointer . '/oneOf/1' => new NamedSpecification("schema Foo1", $second),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedOneOfThrowsException(): void
    {
        $pointer   = '/components/schemas/Foo';
        $expected  = "Unresolved reference: '$pointer'";
        $reference = new Reference(['$ref' => $pointer]);
        $schema    = new Schema([
            'oneOf' => [$reference],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage($expected);
        $this->locator->getNamedSpecifications('Foo', $schema);
    }

    public function testGetNamedSchemasRecursesProperties(): void
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
            $pointer                     => new NamedSpecification('schema Foo', $schema),
            $pointer . '/properties/pet' => new NamedSpecification('schema Foo pet', $propertySchema),
        ];

        self::assertTrue($schema->validate());
        $actual = $this->locator->getNamedSpecifications('Foo', $schema);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReferencedPropertyThrowsException(): void
    {
        $pointer  = '/components/schemas/Foo';
        $expected = "Unresolved reference: '$pointer'";
        $schema   = new Schema([
            'type'       => 'object',
            'properties' => [
                'pet' => new Reference(['$ref' => $pointer]),
            ],
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage($expected);
        $this->locator->getNamedSpecifications('Foo', $schema);
    }
}
