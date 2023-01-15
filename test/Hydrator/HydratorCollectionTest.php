<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection
 */
final class HydratorCollectionTest extends TestCase
{
    public function testFromModelCollectionSkipsEnumModels(): void
    {
        $classModel      = new ClassModel('\\Foo', '/components/schemas/Foo', []);
        $expected        = new HydratorModel('\\FooHydrator', $classModel);
        $modelCollection = new ModelCollection();
        $modelCollection->add($classModel);
        $modelCollection->add(new EnumModel('\\Bar', '/components/schemas/Bar'));

        $hydratorCollection = HydratorCollection::fromModelCollection($modelCollection);
        self::assertCount(1, $hydratorCollection);
        $actual = $hydratorCollection->current();
        self::assertEquals($expected, $actual);
    }

    public function testCollectionIsIterable(): void
    {
        $classModels     = [
            new ClassModel('\\Foo', '/components/schemas/Foo', []),
            new ClassModel('\\Bar', '/components/schemas/Bar', []),
        ];
        $expected        = [
            new HydratorModel('\\FooHydrator', $classModels[0]),
            new HydratorModel('\\BarHydrator', $classModels[1]),
        ];
        $modelCollection = new ModelCollection();
        foreach ($classModels as $model) {
            $modelCollection->add($model);
        }

        $hydratorCollection = HydratorCollection::fromModelCollection($modelCollection);
        $actual             = iterator_to_array($hydratorCollection);
        self::assertEquals($expected, $actual);
    }

    public function testGetHydratorMapReturnsMap(): void
    {
        $expected        = [
            '\\Foo' => '\\FooHydrator',
            '\\Bar' => '\\BarHydrator',
        ];
        $classModels     = [
            new ClassModel('\\Foo', '/components/schemas/Foo', []),
            new ClassModel('\\Bar', '/components/schemas/Bar', []),
        ];
        $modelCollection = new ModelCollection();
        foreach ($classModels as $model) {
            $modelCollection->add($model);
        }

        $hydratorCollection = HydratorCollection::fromModelCollection($modelCollection);
        $actual             = $hydratorCollection->getHydratorMap();
        self::assertSame($expected, $actual);
    }
}
