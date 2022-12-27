<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
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
        $property         = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $this->class      = new ClassModel('\\Foo', '/Foo', [], $property);
    }

    public function testAddMatchingThrowsException(): void
    {
        $this->collection->add($this->class);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Model '\\Foo' already exists");
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
        $property = new SimpleProperty('$bar', 'bar', new PropertyMetadata(), PropertyType::String);
        return [
            'matches'  => [new ClassModel('\\Foo', '/foo', [], $property), true],
            'no_match' => [new ClassModel('\\Bar', '/Bar', [], $property), false],
        ];
    }

    public function testCollectionIsIterable(): void
    {
        $property = new SimpleProperty('$bar', 'bar', new PropertyMetadata(), PropertyType::String);
        $models   = [
            $this->class,
            new ClassModel('\\Bar', '/Bar', [], $property),
        ];
        foreach ($models as $model) {
            $this->collection->add($model);
        }

        foreach ($this->collection as $i => $actual) {
            self::assertSame($models[$i], $actual);
        }
    }
}
