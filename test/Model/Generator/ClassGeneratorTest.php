<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator
 */
final class ClassGeneratorTest extends TestCase
{
    private ClassGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ClassGenerator();
    }

    public function testAddClassIsFinal(): void
    {
        $model = new ClassModel('\\A\\A', '/A', []);
        $added = $this->generator->addClass(new PhpNamespace('A'), $model);
        self::assertSame('A', $added->getName());
        self::assertTrue($added->isFinal());
    }

    public function testAddClassAddsImplements(): void
    {
        $expected = ['A\\AInterface'];
        $model    = new ClassModel('A\\A', '/A', $expected);
        $added    = $this->generator->addClass(new PhpNamespace('A'), $model);
        self::assertSame($expected, $added->getImplements());
    }

    public function testAddClassAddsUses(): void
    {
        $expected   = [
            'C'  => 'B\\C',
            'DC' => 'D\\C',
        ];
        $properties = array_map(fn (string $className): ClassString => new ClassString($className), $expected);
        $model      = new ClassModel(
            '\\A\\B',
            '/B',
            [],
            new UnionProperty('$a', 'a', new PropertyMetadata(), null, ...$properties)
        );
        $namespace  = new PhpNamespace('A');
        $this->generator->addClass($namespace, $model);

        $actual = $namespace->getUses();
        self::assertSame($expected, $actual);
    }

    public function testAddClassAddsConstructorParameter(): void
    {
        $metadata  = new PropertyMetadata('', '', true);
        $model     = new ClassModel(
            '\\A\\B',
            '/A/B',
            [],
            new SimpleProperty('$a', 'a', $metadata, PropertyType::Boolean)
        );
        $namespace = new PhpNamespace('A');
        $class     = $this->generator->addClass($namespace, $model);

        $constructor = $class->getMethod('__construct');
        $parameters  = $constructor->getParameters();
        self::assertArrayHasKey('a', $parameters);
        $parameter = $parameters['a'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);

        self::assertSame('bool', $parameter->getType());
        self::assertTrue($parameter->isReadonly());
        self::assertTrue($parameter->isPrivate());
    }

    public function testAddClassAddsFqnConstructorParameter(): void
    {
        $expected  = '\\A\\C';
        $model     = new ClassModel(
            '\\A\\B',
            '/A/B',
            [],
            new SimpleProperty('$a', 'a', new PropertyMetadata('', '', true), new ClassString($expected))
        );
        $namespace = new PhpNamespace('A');
        $class     = $this->generator->addClass($namespace, $model);

        $constructor = $class->getMethod('__construct');
        $parameters  = $constructor->getParameters();
        self::assertArrayHasKey('a', $parameters);
        $parameter = $parameters['a'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);

        self::assertSame($expected, $parameter->getType());
    }

    /**
     * @dataProvider parameterDefaultProvider
     */
    public function testAddClassSetsParameterDefault(
        bool $isRequired,
        bool $isNullable,
        bool|null $default
    ): void {
        $metadata  = new PropertyMetadata(required: $isRequired, nullable: $isNullable, default: $default);
        $model     = new ClassModel(
            '\\A\\B',
            '/A/B',
            [],
            new SimpleProperty('$a', 'a', $metadata, PropertyType::Boolean)
        );
        $namespace = new PhpNamespace('A');
        $class     = $this->generator->addClass($namespace, $model);

        $constructor = $class->getMethod('__construct');
        $parameters  = $constructor->getParameters();
        self::assertArrayHasKey('a', $parameters);
        $parameter = $parameters['a'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);

        self::assertFalse($parameter->hasDefaultValue());
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool|null}>
     */
    public static function parameterDefaultProvider(): array
    {
        return [
            'required'     => [true, false, null],
            'not_required' => [false, false, null],
            'nullable'     => [false, true, null],
            'default'      => [false, false, true],
        ];
    }

    public function testAddClassAddsMethods(): void
    {
        $metadata  = new PropertyMetadata('', '', true);
        $model     = new ClassModel(
            '\\A\\B',
            '/A/B',
            [],
            new SimpleProperty('$a', 'a', $metadata, PropertyType::String)
        );
        $namespace = new PhpNamespace('A');
        $class     = $this->generator->addClass($namespace, $model);

        self::assertTrue($class->hasMethod('getA'));
        $method = $class->getMethod('getA');
        self::assertTrue($method->isPublic());
        self::assertSame('string', $method->getReturnType());
        self::assertSame('return $this->a;', $method->getBody());
    }
}
