<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property;
use Kynx\Mezzio\OpenApiGenerator\Model\PropertyType;
use KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Model;
use KynxTest\Mezzio\OpenApiGenerator\Model\Asset\Subdir\SubModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection
 */
final class ModelCollectionTest extends TestCase
{
    private ModelCollection $collection;
    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = new ModelCollection();
        $property = new Property('bar', false, PropertyType::String);
        $this->class = new ClassModel(Model::class, 'Foo', $property);
    }

    public function testAddMatchingThrowsException(): void
    {
        $this->collection->add($this->class);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Model class '" . Model::class . "' already exists");
        $this->collection->add(clone $this->class);
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas(ClassModel $test, bool $expected): void
    {
        $this->collection->add($this->class);
        $actual = $this->collection->has($test);
        self::assertSame($expected, $actual);
    }

    public function hasProvider(): array
    {
        return [
            'matches' => [
                new ClassModel(Model::class, 'Foo', new Property('bar', false, PropertyType::String)),
                true
            ],
            'no_match' => [
                new ClassModel(SubModel::class, 'Bar', new Property('bar', false, PropertyType::String)),
                false
            ],
        ];
    }

    public function testCollectionIsIterable(): void
    {
        $models = [
            new ClassModel(Model::class, 'Foo', new Property('bar', false, PropertyType::String)),
            new ClassModel(SubModel::class, 'Bar', new Property('bar', false, PropertyType::String)),
        ];
        foreach ($models as $model) {
            $this->collection->add($model);
        }

        foreach ($this->collection as $i => $actual) {
            self::assertSame($models[$i], $actual);
        }
    }
}
