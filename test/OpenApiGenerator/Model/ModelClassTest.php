<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property;
use Kynx\Mezzio\OpenApiGenerator\Model\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 */
final class ModelClassTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className = '\\Foo';
        $schemaName = 'Bar';
        $property = new Property('foo', true, PropertyType::String);
        $actual   = new ClassModel($className, $schemaName, $property);

        self::assertSame($className, $actual->getClassName());
        self::assertSame($schemaName, $actual->getJsonPointer());
        self::assertSame([$property], $actual->getProperties());
    }

    /**
     * @dataProvider hasMatchesProvider
     */
    public function testMatches(ClassModel $test, bool $expected): void
    {
        $modelClass = new ClassModel('\\A', 'A', new Property('foo', true, PropertyType::String));
        $actual = $modelClass->matches($test);
        self::assertSame($expected, $actual);
    }

    public function hasMatchesProvider(): array
    {
        $property = new Property('foo', true, PropertyType::String);
        return [
            'class' => [new ClassModel('\\B', 'A', $property), false],
            'schema_name' => [new ClassModel('\\A', 'B', $property), true],
            'schema' => [new ClassModel('\\A', 'B', new Property('foo', true, PropertyType::Integer)), true],
        ];
    }
}
