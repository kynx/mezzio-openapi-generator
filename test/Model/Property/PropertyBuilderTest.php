<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use DateTimeImmutable;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\TestCase;

use function array_combine;
use function array_map;
use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder
 */
final class PropertyBuilderTest extends TestCase
{
    private PropertyBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $typeMapper    = new TypeMapper(new DateTimeImmutableMapper());
        $this->builder = new PropertyBuilder($typeMapper);
    }

    /**
     * @dataProvider metadataProvider
     */
    public function testGetPropertySetsMetadata(
        string $key,
        array|string|bool|null $value,
        PropertyMetadata $expected
    ): void {
        $spec       = ['type' => 'string'];
        $spec[$key] = $value;
        $schema     = $this->getSchema('/components/schemas/Foo', $spec);

        $property = $this->builder->getProperty($schema, '$foo', 'foo', false, []);
        $actual   = $property->getMetadata();
        self::assertEquals($expected, $actual);
    }

    public function metadataProvider(): array
    {
        return [
            'title'          => ['title', 'Foo', new PropertyMetadata(...['title' => 'Foo'])],
            'description'    => ['description', 'Foo', new PropertyMetadata(...['description' => 'Foo'])],
            'nullable'       => ['nullable', true, new PropertyMetadata(...['nullable' => true])],
            'deprecated'     => ['deprecated', true, new PropertyMetadata(...['deprecated' => true])],
            'default'        => ['default', 'foo', new PropertyMetadata(...['default' => 'foo'])],
            'example_string' => ['example', 'foo', new PropertyMetadata(...['examples' => ['foo']])],
            'example_array'  => ['example', ['foo'], new PropertyMetadata(...['examples' => ['foo']])],
            'examples'       => ['example', ['foo'], new PropertyMetadata(...['examples' => ['foo']])],
        ];
    }

    public function testGetPropertySetsRequiredMetadata(): void
    {
        $schema = $this->getSchema('/components/schemas/Foo', [
            'type' => 'string',
        ]);

        $property = $this->builder->getProperty($schema, '$foo', 'foo', true, []);
        $actual   = $property->getMetadata();
        self::assertTrue($actual->isRequired());
    }

    public function testGetPropertyReturnsClassProperty(): void
    {
        $expected   = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), new ClassString(self::class));
        $pointer    = '/components/schemas/Foo';
        $schema     = $this->getSchema($pointer, []);
        $classNames = [$pointer => self::class];

        $actual = $this->builder->getProperty(
            $schema,
            $expected->getName(),
            $expected->getOriginalName(),
            false,
            $classNames
        );
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyEnumReturnsUnionProperty(): void
    {
        $types    = [PropertyType::Integer, PropertyType::String];
        $expected = new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, ...$types);
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'enum' => [1, 2, 'bar'],
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyEnumReturnsSimpleProperty(): void
    {
        $expected = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::Integer);
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'enum' => [1, 2, 3, 5, 8, 13],
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyOneOfReturnsUnionOfPropertyTypes(): void
    {
        $types    = [PropertyType::Integer, PropertyType::String];
        $expected = new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, ...$types);
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'oneOf' => [
                ['type' => 'integer'],
                ['type' => 'string'],
            ],
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyOneOfReturnsUnionOfClassNames(): void
    {
        $classes       = [
            '\\Bar',
            '\\Baz',
        ];
        $propertyTypes = array_map(fn (string $class): ClassString => new ClassString($class), $classes);
        $discriminator = new PropertyList(['\\Bar' => [], '\\Baz' => []]);
        $expected      = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$propertyTypes);

        $pointers = [
            '/components/schemas/Bar',
            '/components/schemas/Baz',
        ];
        $oneOf    = [];
        foreach ($pointers as $pointer) {
            $oneOf[] = $this->getSchema($pointer, []);
        }
        $schema = $this->getSchema('/components/schemas/Foo', [
            'oneOf' => $oneOf,
        ]);

        // need to reset pointers to simulate referenced schemas
        foreach ($pointers as $i => $pointer) {
            $oneOf[$i]->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        }

        $actual = $this->builder->getProperty(
            $schema,
            $expected->getName(),
            $expected->getOriginalName(),
            false,
            array_combine($pointers, $classes)
        );
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyAdditionalPropertiesReturnsArrayProperty(): void
    {
        $expected = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), false, PropertyType::String);
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'type'                 => 'object',
            'additionalProperties' => [
                'type' => 'string',
            ],
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyItemsReturnsArrayListProperty(): void
    {
        $expected = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), true, PropertyType::String);
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'type'  => 'object',
            'items' => [
                'type' => 'string',
            ],
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyMapsTypes(): void
    {
        $expected = new SimpleProperty(
            '$foo',
            'foo',
            new PropertyMetadata(),
            new ClassString(DateTimeImmutable::class)
        );
        $pointer  = '/components/schemas/Foo';
        $schema   = $this->getSchema($pointer, [
            'type'   => 'string',
            'format' => 'date-time',
        ]);

        $actual = $this->builder->getProperty($schema, $expected->getName(), $expected->getOriginalName(), false, []);
        self::assertEquals($expected, $actual);
    }

    private function getSchema(string $pointer, array $spec): Schema
    {
        $schema = new Schema($spec);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        return $schema;
    }
}
