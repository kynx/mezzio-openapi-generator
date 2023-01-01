<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 */
final class ClassModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className  = '\\Foo';
        $schemaName = 'Bar';
        $implements = ['\\Bar'];
        $property   = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $actual     = new ClassModel($className, $schemaName, $implements, $property);

        self::assertSame($className, $actual->getClassName());
        self::assertSame($schemaName, $actual->getJsonPointer());
        self::assertSame($implements, $actual->getImplements());
        self::assertSame([$property], $actual->getProperties());
    }

    /**
     * @dataProvider hasMatchesProvider
     */
    public function testMatches(EnumModel|ClassModel|InterfaceModel $test, bool $expected): void
    {
        $property   = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $modelClass = new ClassModel('\\A', 'A', [], $property);
        $actual     = $modelClass->matches($test);
        self::assertSame($expected, $actual);
    }

    public function hasMatchesProvider(): array
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $another  = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::Integer);
        return [
            'class'     => [new ClassModel('\\B', 'A', [], $property), false],
            'pointer'   => [new ClassModel('\\A', 'B', [], $property), true],
            'schema'    => [new ClassModel('\\A', 'A', [], $another), true],
            'enum'      => [new EnumModel('\\A', 'A'), false],
            'interface' => [new InterfaceModel('\\A', 'A'), false],
        ];
    }
}
