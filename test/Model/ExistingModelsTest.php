<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExistingModels::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(ClassModel::class)]
#[UsesClass(EnumCase::class)]
#[UsesClass(EnumModel::class)]
#[UsesClass(InterfaceModel::class)]
#[UsesClass(ModelCollection::class)]
#[UsesClass(ModelException::class)]
#[UsesClass(AbstractProperty::class)]
#[UsesClass(ArrayProperty::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(SimpleProperty::class)]
#[UsesClass(UnionProperty::class)]
final class ExistingModelsTest extends TestCase
{
    private const NAMESPACE = __NAMESPACE__ . '\\Asset\\Existing';

    private ExistingModels $existing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->existing = new ExistingModels(self::NAMESPACE, __DIR__ . '/Asset/Existing');
    }

    public function testUpdateClassNamesReturnsRenamed(): void
    {
        $property   = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::Boolean);
        $case       = new EnumCase('Foo', 'foo');
        $interfaces = [self::NAMESPACE . '\\FooClassInterface'];

        $expected = new ModelCollection();
        $expected->add(new ClassModel(
            self::NAMESPACE . '\\MatchedClass',
            '/components/schemas/FooClass',
            [self::NAMESPACE . '\\MatchedInterface'],
            $property
        ));
        $expected->add(new EnumModel(
            self::NAMESPACE . '\\MatchedEnum',
            '/components/schemas/FooEnum',
            $case
        ));
        $expected->add(new InterfaceModel(
            self::NAMESPACE . '\\MatchedInterface',
            '/components/schemas/FooClass',
            $property
        ));

        $original = new ModelCollection();
        $original->add(new ClassModel(
            self::NAMESPACE . '\\FooClass',
            '/components/schemas/FooClass',
            $interfaces,
            $property
        ));
        $original->add(new EnumModel(
            self::NAMESPACE . '\\FooEnum',
            '/components/schemas/FooEnum',
            $case
        ));
        $original->add(new InterfaceModel(
            self::NAMESPACE . '\\FooClassInterface',
            '/components/schemas/FooClass',
            $property
        ));

        $actual = $this->existing->updateClassNames($original);
        self::assertEquals($expected, $actual);
    }

    public function testUpdateClassNamesReturnsOriginals(): void
    {
        $expected = new ModelCollection();
        $expected->add(new ClassModel(self::NAMESPACE . '\\BarClass', '/components/schemas/BarClass', []));
        $expected->add(new EnumModel(self::NAMESPACE . '\\BarEnum', '/components/schemas/BarEnum'));
        $expected->add(new InterfaceModel(self::NAMESPACE . '\\BarClassInterface', '/components/schemas/BarClass'));

        $actual = $this->existing->updateClassNames($expected);
        $expected->rewind();
        self::assertEquals($expected, $actual);
    }

    public function testUpdateClassNamesRecursesSubdirs(): void
    {
        $expected = new ModelCollection();
        $expected->add(new ClassModel(self::NAMESPACE . '\\Subdir\\SubClass', '/components/schemas/SubClass', []));

        $original = new ModelCollection();
        $original->add(new ClassModel(self::NAMESPACE . '\\Subdir\\BarClass', '/components/schemas/SubClass', []));

        $actual = $this->existing->updateClassNames($original);
        self::assertEquals($expected, $actual);
    }

    public function testUpdateClassNamesInvalidDirectoryReturnsUnaltered(): void
    {
        $path     = __DIR__ . '/Assets/NonExistent';
        $expected = new ModelCollection();
        $existing = new ExistingModels(self::NAMESPACE, $path);

        $actual = $existing->updateClassNames($expected);
        self::assertSame($expected, $actual);
    }

    public function testUpdateClassNamesBrokenAnnotationThrowsException(): void
    {
        $namespace = __NAMESPACE__ . '\\Asset\\Broken';
        $class     = $namespace . '\\BrokenAttribute';
        $expected  = "Invalid OpenApiSchema attribute for class '$class'";
        $existing  = new ExistingModels($namespace, __DIR__ . '/Asset/Broken');

        self::expectException(ModelException::class);
        self::expectExceptionMessage($expected);
        $existing->updateClassNames(new ModelCollection());
    }

    public function testUpdateClassNamesRenamesArrayProperties(): void
    {
        $originalType = new ClassString(self::NAMESPACE . '\\FooEnum', true);
        $expectedType = new ClassString(self::NAMESPACE . '\\MatchedEnum', true);

        $expected = new ModelCollection();
        $expected->add(new ClassModel(
            self::NAMESPACE . '\\MatchedClass',
            '/components/schemas/FooClass',
            [],
            new ArrayProperty('$foo', 'foo', new PropertyMetadata(), true, $expectedType)
        ));
        $expected->add(new EnumModel(
            self::NAMESPACE . '\\MatchedEnum',
            '/components/schemas/FooEnum'
        ));

        $original = new ModelCollection();
        $original->add(new ClassModel(
            self::NAMESPACE . '\\FooClass',
            '/components/schemas/FooClass',
            [],
            new ArrayProperty('$foo', 'foo', new PropertyMetadata(), true, $originalType)
        ));
        $original->add(new EnumModel(
            self::NAMESPACE . '\\FooEnum',
            '/components/schemas/FooEnum'
        ));

        $actual = $this->existing->updateClassNames($original);
        self::assertEquals($expected, $actual);
    }

    public function testUpdateClassNamesRenamesSimpleProperties(): void
    {
        $originalType = new ClassString(self::NAMESPACE . '\\FooEnum', true);
        $expectedType = new ClassString(self::NAMESPACE . '\\MatchedEnum', true);

        $expected = new ModelCollection();
        $expected->add(new ClassModel(
            self::NAMESPACE . '\\MatchedClass',
            '/components/schemas/FooClass',
            [],
            new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $expectedType)
        ));
        $expected->add(new EnumModel(
            self::NAMESPACE . '\\MatchedEnum',
            '/components/schemas/FooEnum'
        ));

        $original = new ModelCollection();
        $original->add(new ClassModel(
            self::NAMESPACE . '\\FooClass',
            '/components/schemas/FooClass',
            [],
            new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $originalType)
        ));
        $original->add(new EnumModel(
            self::NAMESPACE . '\\FooEnum',
            '/components/schemas/FooEnum'
        ));

        $actual = $this->existing->updateClassNames($original);
        self::assertEquals($expected, $actual);
    }

    public function testUpdateClassNamesRenamesUnionProperties(): void
    {
        $originalType = new ClassString(self::NAMESPACE . '\\FooEnum', true);
        $expectedType = new ClassString(self::NAMESPACE . '\\MatchedEnum', true);

        $expected = new ModelCollection();
        $expected->add(new ClassModel(
            self::NAMESPACE . '\\MatchedClass',
            '/components/schemas/FooClass',
            [],
            new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, $expectedType, PropertyType::String)
        ));
        $expected->add(new EnumModel(
            self::NAMESPACE . '\\MatchedEnum',
            '/components/schemas/FooEnum'
        ));

        $original = new ModelCollection();
        $original->add(new ClassModel(
            self::NAMESPACE . '\\FooClass',
            '/components/schemas/FooClass',
            [],
            new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, $originalType, PropertyType::String)
        ));
        $original->add(new EnumModel(
            self::NAMESPACE . '\\FooEnum',
            '/components/schemas/FooEnum'
        ));

        $actual = $this->existing->updateClassNames($original);
        self::assertEquals($expected, $actual);
    }
}
