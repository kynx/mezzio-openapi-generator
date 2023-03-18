<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 */
final class UnionPropertyTest extends TestCase
{
    public function testConstructorSetsMembers(): void
    {
        $members  = [PropertyType::Integer, new ClassString('\\Foo')];
        $property = new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, ...$members);
        self::assertSame($members, $property->getTypes());
    }

    /**
     * @dataProvider discriminatorProvider
     */
    public function testConstructorSetsDiscriminator(PropertyList|PropertyValue|null $discriminator): void
    {
        $members  = [new ClassString('\\Foo'), new ClassString('\\Bar')];
        $property = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$members);
        self::assertSame($discriminator, $property->getDiscriminator());
    }

    public function discriminatorProvider(): array
    {
        return [
            'property_list'  => [new PropertyList(['\\Foo' => ['a'], '\\Bar' => ['b']])],
            'property_value' => [new PropertyValue('foo', ['foo' => '\\Foo', 'bar' => '\\Bar'])],
            'null'           => [null],
        ];
    }

    public function testGetPhpTypeReturnsUnion(): void
    {
        $expected      = '\\Foo\\Bar|\\Foo\\Baz';
        $members       = [new ClassString('\\Foo\\Bar'), new ClassString('\\Foo\\Baz')];
        $discriminator = new PropertyValue('foo', []);
        $property      = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$members);

        $actual = $property->getPhpType();
        self::assertSame($expected, $actual);
    }

    public function testGetUsesReturnsTypes(): void
    {
        $expected      = ['\\Foo\\Bar', '\\Foo\\Baz'];
        $members       = [new ClassString('\\Foo\\Bar'), new ClassString('\\Foo\\Baz')];
        $discriminator = new PropertyValue('foo', []);
        $property      = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$members);

        $actual = $property->getUses();
        self::assertSame($expected, $actual);
    }

    public function testGetDocBlocTypeReturnsNull(): void
    {
        $members       = [new ClassString('\\Foo\\Bar'), new ClassString('\\Foo\\Baz')];
        $discriminator = new PropertyValue('foo', []);
        $property      = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$members);

        $actual = $property->getDocBlockType();
        self::assertNull($actual);
    }

    public function testGetDocBockTypeForUnionReturnsType(): void
    {
        $epxected      = 'Bar|Baz';
        $members       = [new ClassString('\\Foo\\Bar'), new ClassString('\\Foo\\Baz')];
        $discriminator = new PropertyValue('foo', []);
        $property      = new UnionProperty('$foo', 'foo', new PropertyMetadata(), $discriminator, ...$members);

        $actual = $property->getDocBlockType(true);
        self::assertSame($epxected, $actual);
    }
}
