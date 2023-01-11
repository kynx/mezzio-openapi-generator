<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

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
        $members  = [PropertyType::Integer, '\\Foo'];
        $property = new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, ...$members);
        self::assertSame($members, $property->getMembers());
    }

    /**
     * @dataProvider discriminatorProvider
     */
    public function testConstructorSetsDiscriminator(PropertyList|PropertyValue|null $discriminator): void
    {
        $members  = ['\\Foo', '\\Bar'];
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
}
