<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiHydrator;
use Kynx\Mezzio\OpenApi\Hydrator\DateTimeImmutableHydrator;
use Kynx\Mezzio\OpenApi\Hydrator\Exception\HydrationException;
use Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use PHPUnit\Framework\TestCase;
use TypeError;

use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator
 */
final class HydratorGeneratorTest extends TestCase
{
    use GeneratorTrait;

    private const MODEL_NAMESPACE = 'Api\\Model';

    private HydratorGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $overrideHydrators = [
            DateTimeImmutable::class => DateTimeImmutableHydrator::class,
        ];

        $this->generator = new HydratorGenerator(
            $overrideHydrators
        );
    }

    public function testGenerateReturnsHydrator(): void
    {
        $className      = 'Foo';
        $fullyQualified = self::MODEL_NAMESPACE . '\\' . $className;
        $pointer        = '/components/schemas/Foo';
        $classModel     = new ClassModel($fullyQualified, $pointer, []);
        $hydratorName   = $className . 'Hydrator';
        $model          = new HydratorModel(self::MODEL_NAMESPACE . '\\' . $hydratorName, $classModel);
        $hydratorMap    = [$classModel->getClassName() => $model->getClassName()];

        $file         = $this->generator->generate($model, $hydratorMap);
        $namespace    = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $expectedUses = [
            'OpenApiHydrator'    => OpenApiHydrator::class,
            'HydrationException' => HydrationException::class,
            'HydratorInterface'  => HydratorInterface::class,
            'TypeError'          => TypeError::class,
        ];
        self::assertSame($expectedUses, $namespace->getUses());

        $class = $this->getClass($namespace, $hydratorName);
        self::assertSame([HydratorInterface::class], $class->getImplements());
        self::assertTrue($class->isFinal());

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiHydrator::class, $attribute->getName());
        self::assertSame([$pointer], $attribute->getArguments());

        $method = $this->getHydrateMethod($class);
        self::assertSame($fullyQualified, $method->getReturnType());

        $parameters = $method->getParameters();
        self::assertCount(1, $parameters);
        self::assertArrayHasKey('data', $parameters);
        $parameter = $parameters['data'];
        self::assertSame('data', $parameter->getName());
        self::assertSame('array', $parameter->getType());

        $expected = <<<EOB
        try {
            return new $className(...\$data);
        } catch (TypeError \$error) {
            throw HydrationException::fromThrowable($className::class, \$error);
        }
        EOB;
        $actual   = trim($method->getBody());
        self::assertSame($expected, $actual);
    }

    public function testGeneratePopulatesPropertyMap(): void
    {
        $expected    = [
            'foo' => 'bar',
            'baz' => 'baz',
        ];
        $properties  = [
            new SimpleProperty('$bar', 'foo', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$baz', 'baz', new PropertyMetadata(), PropertyType::Integer),
        ];
        $classModel  = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '', [], ...$properties);
        $model       = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $hydratorMap = [$classModel->getClassName() => $model->getClassName()];

        $file      = $this->generator->generate($model, $hydratorMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $uses      = $namespace->getUses();
        self::assertArrayHasKey('HydratorUtil', $uses);

        $class    = $this->getClass($namespace, 'FooHydrator');
        $constant = $this->getConstant($class, 'PROPERTY_MAP');

        self::assertSame($expected, $constant->getValue());
    }

    public function testGenerateAddsValueDiscriminator(): void
    {
        $expected      = [
            'foo' => [
                'key' => 'type',
                'map' => [
                    'a' => new Literal('BarHydrator::class'),
                    'b' => new Literal('BazHydrator::class'),
                ],
            ],
        ];
        $bar           = self::MODEL_NAMESPACE . '\\Foo\\Bar';
        $baz           = self::MODEL_NAMESPACE . '\\Foo\\Baz';
        $barString     = new ClassString($bar);
        $bazString     = new ClassString($baz);
        $discriminator = new PropertyValue('type', ['a' => $bar, 'b' => $baz]);
        $property      = new UnionProperty(
            '$foo',
            'foo',
            new PropertyMetadata(),
            $discriminator,
            $barString,
            $bazString
        );
        $classModel    = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '/foo', [], $property);
        $model         = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $hydratorMap   = [
            $classModel->getClassName() => $model->getClassName(),
            $bar                        => self::MODEL_NAMESPACE . '\\Foo\\BarHydrator',
            $baz                        => self::MODEL_NAMESPACE . '\\Foo\\BazHydrator',
        ];

        $file      = $this->generator->generate($model, $hydratorMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $uses      = $namespace->getUses();
        self::assertArrayHasKey('BarHydrator', $uses);
        self::assertArrayHasKey('BazHydrator', $uses);
        self::assertArrayHasKey('HydratorUtil', $uses);

        $class    = $this->getClass($namespace, 'FooHydrator');
        $constant = $this->getConstant($class, 'VALUE_DISCRIMINATORS');
        self::assertEquals($expected, $constant->getValue());
        $constant = $this->getConstant($class, 'ARRAY_PROPERTIES');
        self::assertSame([], $constant->getValue());

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $expected = '$data = HydratorUtil::hydrateDiscriminatorValues($data, self::ARRAY_PROPERTIES, self::VALUE_DISCRIMINATORS);';
        $method   = $this->getHydrateMethod($class);
        $body     = $method->getBody();
        self::assertStringContainsString($expected, $body);
    }

    public function testGenerateAddsPropertyDiscriminator(): void
    {
        $expected      = [
            'foo' => [
                new Literal("BarHydrator::class => ['a', 'b']"),
                new Literal("BazHydrator::class => ['c', 'd']"),
            ],
        ];
        $bar           = self::MODEL_NAMESPACE . '\\Foo\\Bar';
        $baz           = self::MODEL_NAMESPACE . '\\Foo\\Baz';
        $barString     = new ClassString($bar);
        $bazString     = new ClassString($baz);
        $discriminator = new PropertyList([$bar => ['a', 'b'], $baz => ['c', 'd']]);
        $property      = new UnionProperty(
            '$foo',
            'foo',
            new PropertyMetadata(),
            $discriminator,
            $barString,
            $bazString
        );
        $classModel    = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '/foo', [], $property);
        $model         = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $classMap      = [
            $classModel->getClassName() => $model->getClassName(),
            $bar                        => self::MODEL_NAMESPACE . '\\Foo\\BarHydrator',
            $baz                        => self::MODEL_NAMESPACE . '\\Foo\\BazHydrator',
        ];

        $file      = $this->generator->generate($model, $classMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $uses      = $namespace->getUses();
        self::assertArrayHasKey('BarHydrator', $uses);
        self::assertArrayHasKey('BazHydrator', $uses);
        self::assertArrayHasKey('HydratorUtil', $uses);

        $class    = $this->getClass($namespace, 'FooHydrator');
        $constant = $this->getConstant($class, 'PROPERTY_DISCRIMINATORS');
        self::assertEquals($expected, $constant->getValue());
        $constant = $this->getConstant($class, 'ARRAY_PROPERTIES');
        self::assertSame([], $constant->getValue());

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $expected = '$data = HydratorUtil::hydrateDiscriminatorList($data, self::ARRAY_PROPERTIES, self::PROPERTY_DISCRIMINATORS);';
        $method   = $this->getHydrateMethod($class);
        $body     = $method->getBody();
        self::assertStringContainsString($expected, $body);
    }

    public function testGenerateAddsPropertyHydrators(): void
    {
        $expected   = [
            'bar' => new Literal('BarHydrator::class'),
            'baz' => new Literal('BazHydrator::class'),
        ];
        $properties = [
            new SimpleProperty('$bar', 'bar', new PropertyMetadata(), new ClassString(self::MODEL_NAMESPACE . '\\Bar')),
            new SimpleProperty('$baz', 'baz', new PropertyMetadata(), new ClassString(self::MODEL_NAMESPACE . '\\Baz')),
        ];
        $classModel = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '/component/schemas/Foo', [], ...$properties);
        $model      = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $classMap   = [
            $classModel->getClassName()     => $model->getClassName(),
            self::MODEL_NAMESPACE . '\\Bar' => self::MODEL_NAMESPACE . '\\Foo\\BarHydrator',
            self::MODEL_NAMESPACE . '\\Baz' => self::MODEL_NAMESPACE . '\\Foo\\BazHydrator',
        ];

        $file      = $this->generator->generate($model, $classMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $uses      = $namespace->getUses();
        self::assertArrayHasKey('BarHydrator', $uses);
        self::assertArrayHasKey('BazHydrator', $uses);
        self::assertArrayHasKey('HydratorUtil', $uses);

        $class    = $this->getClass($namespace, 'FooHydrator');
        $constant = $this->getConstant($class, 'PROPERTY_HYDRATORS');
        self::assertEquals($expected, $constant->getValue());
        $constant = $this->getConstant($class, 'ARRAY_PROPERTIES');
        self::assertSame([], $constant->getValue());

        $expected = '$data = HydratorUtil::hydrateProperties($data, self::ARRAY_PROPERTIES, self::PROPERTY_HYDRATORS);';
        $method   = $this->getHydrateMethod($class);
        $body     = $method->getBody();
        self::assertStringContainsString($expected, $body);
    }

    public function testGeneratePopulatesArrayProperties(): void
    {
        $expected   = ['bar'];
        $properties = [
            new ArrayProperty(
                '$bar',
                'bar',
                new PropertyMetadata(),
                true,
                new ClassString(self::MODEL_NAMESPACE . '\\Bar')
            ),
        ];
        $classModel = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '/component/schemas/Foo', [], ...$properties);
        $model      = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $classMap   = [
            $classModel->getClassName()     => $model->getClassName(),
            self::MODEL_NAMESPACE . '\\Bar' => self::MODEL_NAMESPACE . '\\Foo\\BarHydrator',
        ];

        $file      = $this->generator->generate($model, $classMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $uses      = $namespace->getUses();
        self::assertArrayHasKey('HydratorUtil', $uses);

        $class    = $this->getClass($namespace, 'FooHydrator');
        $constant = $this->getConstant($class, 'ARRAY_PROPERTIES');
        self::assertSame($expected, $constant->getValue());
    }

    public function testGenerateAddsEnums(): void
    {
        $expected   = [
            'bar' => new Literal('Bar::class'),
        ];
        $properties = [
            new SimpleProperty(
                '$bar',
                'bar',
                new PropertyMetadata(),
                new ClassString(self::MODEL_NAMESPACE . '\\Bar', true)
            ),
        ];
        $classModel = new ClassModel(self::MODEL_NAMESPACE . '\\Foo', '/component/schemas/Foo', [], ...$properties);
        $model      = new HydratorModel(self::MODEL_NAMESPACE . '\\FooHydrator', $classModel);
        $classMap   = [$classModel->getClassName() => $model->getClassName()];

        $file      = $this->generator->generate($model, $classMap);
        $namespace = $this->getNamespace($file, self::MODEL_NAMESPACE);
        $class     = $this->getClass($namespace, 'FooHydrator');
        $constant  = $this->getConstant($class, 'ENUMS');
        self::assertEquals($expected, $constant->getValue());
        $constant = $this->getConstant($class, 'ARRAY_PROPERTIES');
        self::assertSame([], $constant->getValue());

        $expected = '$data = HydratorUtil::hydrateEnums($data, self::ARRAY_PROPERTIES, self::ENUMS);';
        $method   = $this->getHydrateMethod($class);
        $body     = $method->getBody();
        self::assertStringContainsString($expected, $body);
    }

    private function getHydrateMethod(ClassType $class): Method
    {
        self::assertCount(1, $class->getMethods());
        return $this->getMethod($class, 'hydrate');
    }
}
