<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty
 */
final class ArrayPropertyTest extends TestCase
{
    public function testGettersReturnValues(): void
    {
        $isList     = true;
        $memberType = PropertyType::Integer;
        $property   = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), $isList, $memberType);

        self::assertSame($isList, $property->isList());
        self::assertSame($memberType, $property->getType());
        self::assertSame([$memberType], $property->getTypes());
    }

    public function testGetPhpTypeReturnsArray(): void
    {
        $property = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), false, PropertyType::String);

        $actual = $property->getPhpType();
        self::assertSame('array', $actual);
    }

    /**
     * @dataProvider getUsesProvider
     */
    public function testGetUsesReturnUse(ClassString|PropertyType $type, array $expected): void
    {
        $property = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), false, $type);

        $actual = $property->getUses();
        self::assertSame($expected, $actual);
    }

    public function getUsesProvider(): array
    {
        return [
            'scalar' => [PropertyType::String, []],
            'class'  => [new ClassString('\\A\\B'), ['\\A\\B']],
        ];
    }

    /**
     * @dataProvider getDocBlockProvider
     */
    public function testGetDocBlockTypeReturnsType(ClassString|PropertyType $type, bool $isList, string $expected): void
    {
        $property = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), $isList, $type);

        $actual = $property->getDocBlockType();
        self::assertSame($expected, $actual);
    }

    public function getDocBlockProvider(): array
    {
        return [
            'array_string' => [PropertyType::String, false, 'array<string, string>'],
            'array_class'  => [new ClassString('\\A'), false, 'array<string, A>'],
            'list_string'  => [PropertyType::String, true, 'list<string>'],
            'list_class'   => [new ClassString('\\A\\B'), true, 'list<B>'],
        ];
    }
}
