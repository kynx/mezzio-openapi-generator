<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayProperty::class)]
#[UsesClass(PropertyMetadata::class)]
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

    #[DataProvider('getUsesProvider')]
    public function testGetUsesReturnUse(ClassString|PropertyType $type, array $expected): void
    {
        $property = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), false, $type);

        $actual = $property->getUses();
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: PropertyType|ClassString, 1: array}>
     */
    public static function getUsesProvider(): array
    {
        return [
            'scalar' => [PropertyType::String, []],
            'class'  => [new ClassString('\\A\\B'), ['\\A\\B']],
        ];
    }

    #[DataProvider('getDocBlockProvider')]
    public function testGetDocBlockTypeReturnsType(
        ClassString|PropertyType $type,
        bool $isList,
        bool $required,
        bool $nullable,
        string $expected
    ): void {
        $metadata = new PropertyMetadata(required: $required, nullable: $nullable);
        $property = new ArrayProperty('$foo', 'foo', $metadata, $isList, $type);

        $actual = $property->getDocBlockType();
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: PropertyType|ClassString, 1: bool, 2: bool, 3: bool, 4: string}>
     */
    public static function getDocBlockProvider(): array
    {
        return [
            'array_string'          => [PropertyType::String, false, false, false, 'array<string, string>|null'],
            'array_string_required' => [PropertyType::String, false, true, false, 'array<string, string>'],
            'array_string_nullable' => [PropertyType::String, false, true, true, 'array<string, string>|null'],
            'array_class'           => [new ClassString('\\A'), false, true, false, 'array<string, A>'],
            'list_string'           => [PropertyType::String, true, true, false, 'list<string>'],
            'list_class'            => [new ClassString('\\A\\B'), true, true, false, 'list<B>'],
        ];
    }
}
