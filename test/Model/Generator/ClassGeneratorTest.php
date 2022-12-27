<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;

/**
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

    public function testAddClassAddsUses(): void
    {
        $expected  = [
            'C'  => 'B\\C',
            'DC' => 'D\\C',
        ];
        $model     = new ClassModel(
            '\\A\\B',
            '/B',
            [],
            new UnionProperty('$a', 'a', new PropertyMetadata(), ...$expected)
        );
        $namespace = new PhpNamespace('A');
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

    /**
     * @dataProvider parameterDefaultProvider
     */
    public function testAddClassSetsParameterDefault(
        bool $isRequired,
        bool $isNullable,
        bool|null $default,
        bool $hasDefault,
        bool|null $expected
    ): void {
        $metadata  = new PropertyMetadata('', '', $isRequired, $isNullable, false, $default);
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

        self::assertSame($hasDefault, $parameter->hasDefaultValue());
        if ($hasDefault) {
            /** @psalm-suppress MixedAssignment */
            $actual = $parameter->getDefaultValue();
            self::assertSame($expected, $actual);
        }
    }

    public function parameterDefaultProvider(): array
    {
        return [
            'required'     => [true, false, null, false, null],
            'not_required' => [false, false, null, true, null],
            'nullable'     => [false, true, null, true, null],
            'default'      => [false, false, true, true, true],
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
