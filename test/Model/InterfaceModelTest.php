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
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 */
final class InterfaceModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className      = '\\Foo';
        $jsonPointer    = '/components/schemas/Foo';
        $properties     = [new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String)];
        $interfaceModel = new InterfaceModel($className, $jsonPointer, ...$properties);
        self::assertSame($className, $interfaceModel->getClassName());
        self::assertSame($jsonPointer, $interfaceModel->getJsonPointer());
        self::assertSame($properties, $interfaceModel->getProperties());
    }

    /**
     * @dataProvider matchesProvider
     */
    public function testMatches(EnumModel|ClassModel|InterfaceModel $test, bool $expected): void
    {
        $property       = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $interfaceModel = new InterfaceModel('\\A', '/A', $property);
        $actual         = $interfaceModel->matches($test);
        self::assertSame($expected, $actual);
    }

    public function matchesProvider(): array
    {
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $another  = new SimpleProperty('$bar', 'bar', new PropertyMetadata(), PropertyType::String);
        return [
            'interface' => [new InterfaceModel('\\B', '/A', $property), false],
            'pointer'   => [new InterfaceModel('\\A', '/B', $property), true],
            'property'  => [new InterfaceModel('\\A', '/A', $another), true],
            'class'     => [new ClassModel('\\A', '/A', [], $property), false],
            'enum'      => [new EnumModel('\\A', '/A'), false],
        ];
    }
}
