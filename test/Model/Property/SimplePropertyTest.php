<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 */
final class SimplePropertyTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $type     = PropertyType::Integer;
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $type);
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        self::assertSame($type, $property->getType());
        self::assertSame([$type], $property->getTypes());
    }

    /**
     * @dataProvider getPhpTypeProvider
     */
    public function testGetPhpTypeReturnsType(ClassString|PropertyType $type, string $expected): void
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $type);

        $actual = $property->getPhpType();
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: PropertyType|ClassString, 1: string}>
     */
    public static function getPhpTypeProvider(): array
    {
        return [
            'class'    => [new ClassString('\\A\\B'), '\\A\\B'],
            'php_type' => [PropertyType::String, 'string'],
        ];
    }

    /**
     * @dataProvider getUsesProvider
     */
    public function testGetUsesReturnUses(ClassString|PropertyType $type, array $expected): void
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $type);

        $actual = $property->getUses();
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: PropertyType|ClassString, 1: array}>
     */
    public static function getUsesProvider(): array
    {
        return [
            'class'    => [new ClassString('\\A\\B'), ['\\A\\B']],
            'php_type' => [PropertyType::String, []],
        ];
    }

    public function testGetDocBlockTypeReturnsNull(): void
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);

        $actual = $property->getDocBlockType();
        self::assertNull($actual);
    }

    public function testGetDocBlockTypeForUnionReturnsPhpType(): void
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);

        $actual = $property->getDocBlockType(true);
        self::assertSame('string', $actual);
    }

    public function testGetDocBlockTypeForUnionReturnsClassType(): void
    {
        $type     = new ClassString('Foo\\Bar');
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $type);

        $actual = $property->getDocBlockType(true);
        self::assertSame('Bar', $actual);
    }
}
