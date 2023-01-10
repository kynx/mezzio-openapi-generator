<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 *
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
            public function getOrderedParameters(AbstractClassLikeModel $model): array
            {
                return parent::getOrderedParameters($model);
            }

            public function getClassLikeName(AbstractClassLikeModel|EnumModel $modelClass): string
            {
                return parent::getClassLikeName($modelClass);
            }

            public function getMethodName(PropertyInterface $property): string
            {
                return parent::getMethodName($property);
            }

            public function getType(PropertyInterface $property): string
            {
                return parent::getType($property);
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
        // phpcs:disable Generic.Files.LineLength.TooLong
        $nullable = new SimpleProperty('$a', 'a', new PropertyMetadata(...['required' => true, 'nullable' => true]), PropertyType::String);
        $default  = new SimpleProperty('$b', 'b', new PropertyMetadata(...['required' => true, 'default' => 'foo']), PropertyType::String);
        $required = new SimpleProperty('$c', 'c', new PropertyMetadata(...['required' => true]), PropertyType::String);
        $none     = new SimpleProperty('$d', 'd', new PropertyMetadata(), PropertyType::String);
        $another  = new SimpleProperty('$e', 'e', new PropertyMetadata(), PropertyType::String);
        // phpcs:enable

        return [
            'nullable_none'     => [$none, $nullable, [$nullable, $none]],
            'default_none'      => [$none, $default, [$default, $none]],
            'default_nullable'  => [$nullable, $default, [$default, $nullable]],
            'required_none'     => [$none, $required, [$required, $none]],
            'required_default'  => [$default, $required, [$required, $default]],
            'required_nullable' => [$nullable, $required, [$required, $nullable]],
            'none_another'      => [$another, $none, [$none, $another]],
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
        $actual = $this->generator->getType($property);
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
            'union'        => [new UnionProperty('$a', 'a', $required, '\\A\\B', '\\A\\C'), '\\A\\B|\\A\\C'],
        ];
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
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'array_php'  => [[new ArrayProperty('$a', 'a', $metadata, false, PropertyType::String)], []],
            'array_uri'  => [[new ArrayProperty('$a', 'a', $metadata, false, PropertyType::Uri)], [UriInterface::class => null]],
            'array'      => [[new ArrayProperty('$a', 'a', $metadata, false, '\\A')], ['\\A' => null]],
            'simple_php' => [[new SimpleProperty('$a', 'a', $metadata, PropertyType::String)], []],
            'simple_uri' => [[new SimpleProperty('$a', 'a', $metadata, PropertyType::Uri)], [UriInterface::class => null]],
            'simple'     => [[new SimpleProperty('$a', 'a', $metadata, '\\A')], ['\\A' => null]],
            'union_php'  => [[new UnionProperty('$a', 'a', $metadata, PropertyType::String)], []],
            'union_uri'  => [[new UnionProperty('$a', 'a', $metadata, PropertyType::Uri)], [UriInterface::class => null]],
            'union'      => [[new UnionProperty('$a', 'a', $metadata, '\\A', '\\B')], ['\\A' => null, '\\B' => null]],
        ];
        // phpcs:enable
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
