<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 * @psalm-suppress InaccessibleMethod
 */
final class AbstractGeneratorTest extends TestCase
{
    private AbstractGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         */
        $this->generator = new class extends AbstractGenerator {
            public function getOrderedParameters(ClassModel|InterfaceModel $model): array
            {
                return parent::getOrderedParameters($model);
            }

            public function getClassLikeName(ClassModel|EnumModel|InterfaceModel $modelClass): string
            {
                return parent::getClassLikeName($modelClass);
            }

            public function getMethodName(PropertyInterface $property): string
            {
                return parent::getMethodName($property);
            }

            public function getType(PropertyInterface $property, array $aliases): string
            {
                return parent::getType($property, $aliases);
            }

            public function getPropertyUses(array $properties): array
            {
                return parent::getPropertyUses($properties);
            }
        };
    }

    /**
     * @dataProvider orderedParametersProvider
     */
    public function testGetOrderedParametersOrdersParameters(
        PropertyInterface $a,
        PropertyInterface $b,
        array $expected
    ): void {
        $model  = new ClassModel('\\Foo', '/Foo', [], $a, $b);
        $actual = $this->generator->getOrderedParameters($model);
        self::assertSame($expected, $actual);
    }

    public function orderedParametersProvider(): array
    {
        $nullable = new SimpleProperty('$a', 'a', new PropertyMetadata(...['nullable' => true]), PropertyType::String);
        $default  = new SimpleProperty('$b', 'b', new PropertyMetadata(...['default' => 'foo']), PropertyType::String);
        $required = new SimpleProperty('$c', 'c', new PropertyMetadata(...['required' => true]), PropertyType::String);
        $none     = new SimpleProperty('$d', 'd', new PropertyMetadata(), PropertyType::String);

        return [
            'nullable_none'    => [$nullable, $none, [$none, $nullable]],
            'default_none'     => [$default, $none, [$none, $default]],
            'default_nullable' => [$default, $nullable, [$nullable, $default]],
            'none_required'    => [$none, $required, [$required, $none]],
        ];
    }

    public function testGetClassLikeNameReturnsName(): void
    {
        $expected = 'C';
        $model    = new ClassModel('\\A\\B\\C', '/c', []);
        $actual   = $this->generator->getClassLikeName($model);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider getMethodNameProvider
     */
    public function testGetMethodName(PropertyInterface $property, string $expected): void
    {
        $actual = $this->generator->getMethodName($property);
        self::assertSame($expected, $actual);
    }

    public function getMethodNameProvider(): array
    {
        return [
            'bool'      => [new SimpleProperty('$a', 'a', new PropertyMetadata(), PropertyType::Boolean), 'isA'],
            'bool_is'   => [new SimpleProperty('$isA', 'isA', new PropertyMetadata(), PropertyType::Boolean), 'isA'],
            'string'    => [new SimpleProperty('$a', 'a', new PropertyMetadata(), PropertyType::String), 'getA'],
            'string_is' => [new SimpleProperty('$isA', 'isA', new PropertyMetadata(), PropertyType::String), 'getIsA'],
            'union'     => [new UnionProperty('$a', 'a', new PropertyMetadata(), '\\A'), 'getA'],
        ];
    }

    /**
     * @dataProvider getTypeProvider
     */
    public function testGetType(PropertyInterface $property, string $expected): void
    {
        $actual = $this->generator->getType($property, []);
        self::assertSame($expected, $actual);
    }

    public function getTypeProvider(): array
    {
        $nullable    = new PropertyMetadata('', '', false, true);
        $required    = new PropertyMetadata('', '', true, false);
        $notRequired = new PropertyMetadata('', '', false, false);
        return [
            'php'          => [new SimpleProperty('$a', 'a', $required, PropertyType::Boolean), 'bool'],
            'nullable'     => [new SimpleProperty('$a', 'a', $nullable, PropertyType::Boolean), 'bool|null'],
            'not_required' => [new SimpleProperty('$a', 'a', $notRequired, PropertyType::Boolean), 'bool|null'],
            'array'        => [new ArrayProperty('$a', 'a', $required, false, PropertyType::String), 'array'],
            'list'         => [new ArrayProperty('$a', 'a', $required, true, PropertyType::String), 'array'],
            'union'        => [new UnionProperty('$a', 'a', $required, '\\A\\B', '\\A\\C'), 'B|C'],
        ];
    }

    public function testGetTypeUsesAliases(): void
    {
        $expected = 'AB';
        $property = new SimpleProperty('$a', 'a', new PropertyMetadata('', '', true), '\\A\\B');
        $aliases  = ['\\A\\B' => $expected];

        $actual = $this->generator->getType($property, $aliases);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider getPropertyUsesProvider
     * @param list<PropertyInterface> $properties
     */
    public function testGetPropertyUses(array $properties, array $expected): void
    {
        $actual = $this->generator->getPropertyUses($properties);
        self::assertSame($expected, $actual);
    }

    public function getPropertyUsesProvider(): array
    {
        $metadata = new PropertyMetadata();
        return [
            'array_php'  => [[new ArrayProperty('$a', 'a', $metadata, false, PropertyType::String)], []],
            'array'      => [[new ArrayProperty('$a', 'a', $metadata, false, '\\A')], ['\\A' => null]],
            'simple_php' => [[new SimpleProperty('$a', 'a', $metadata, PropertyType::String)], []],
            'simple'     => [[new SimpleProperty('$a', 'a', $metadata, '\\A')], ['\\A' => null]],
            'union_php'  => [[new UnionProperty('$a', 'a', $metadata, PropertyType::String)], []],
            'union'      => [[new UnionProperty('$a', 'a', $metadata, '\\A', '\\B')], ['\\A' => null, '\\B' => null]],
        ];
    }

    public function testGetPropertyUsesOrdersUses(): void
    {
        $expected   = [
            '\\A\\B' => null,
            '\\B\\C' => null,
            '\\B\\D' => null,
        ];
        $metadata   = new PropertyMetadata();
        $properties = [
            new SimpleProperty('$a', 'a', $metadata, '\\B\\D'),
            new SimpleProperty('$b', 'b', $metadata, '\\B\\C'),
            new SimpleProperty('$d', 'd', $metadata, '\\A\\B'),
        ];

        $actual = $this->generator->getPropertyUses($properties);
        self::assertSame($expected, $actual);
    }

    public function testGetPropertyUsesCreatesAliases(): void
    {
        $expected   = [
            '\\A'       => null,
            '\\B\\A'    => 'BA',
            '\\C\\B\\A' => 'CBA',
        ];
        $metadata   = new PropertyMetadata();
        $properties = [
            new SimpleProperty('$a', 'a', $metadata, '\\A'),
            new SimpleProperty('$b', 'b', $metadata, '\\B\\A'),
            new SimpleProperty('$d', 'd', $metadata, '\\C\\B\\A'),
        ];

        $actual = $this->generator->getPropertyUses($properties);
        self::assertSame($expected, $actual);
    }

    public function testGetPropertyUsesDuplicateAliasesFqcn(): void
    {
        $expected   = [
            '\\AB'   => null,
            '\\A\\B' => '\\A\\B',
            '\\B'    => null,
        ];
        $metadata   = new PropertyMetadata();
        $properties = [
            new SimpleProperty('$a', 'a', $metadata, '\\B'),
            new SimpleProperty('$b', 'b', $metadata, '\\AB'),
            new SimpleProperty('$d', 'd', $metadata, '\\A\\B'),
        ];

        $actual = $this->generator->getPropertyUses($properties);
        self::assertSame($expected, $actual);
    }
}
