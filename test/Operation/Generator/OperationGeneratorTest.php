<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationGenerator
 */
final class OperationGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use OperationTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Foo\\Get';

    private OperationGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new OperationGenerator();
    }

    public function testGenerateReturnsOperationFile(): void
    {
        $className = self::NAMESPACE . '\\Operation';
        $pointer   = '/paths/foo/get';
        $operation = new OperationModel($className, $pointer, null, null, null, null, []);

        $file = $this->generator->generate($operation);
        self::assertTrue($file->hasStrictTypes());

        $namespace    = $this->getNamespace($file, self::NAMESPACE);
        $expectedUses = [
            'OpenApiOperation' => OpenApiOperation::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        $class = $this->getClass($namespace, 'Operation');
        self::assertTrue($class->isFinal());

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiOperation::class, $attribute->getName());
        self::assertSame([$pointer], $attribute->getArguments());

        $constructor = $this->getMethod($class, '__construct');
        self::assertSame([], $constructor->getParameters());
        self::assertEmpty($constructor->getBody());
    }

    public function testGenerateAddsConstructorParameterAndGetter(): void
    {
        $className  = self::NAMESPACE . '\\Operation';
        $pointer    = '/paths/foo/get';
        $pathParams = $this->getPathParams(self::NAMESPACE);
        $operation  = new OperationModel($className, $pointer, $pathParams, null, null, null, []);

        $file      = $this->generator->generate($operation);
        $namespace = $this->getNamespace($file, self::NAMESPACE);

        $class = $this->getClass($namespace, 'Operation');

        $constructor = $this->getMethod($class, '__construct');
        $parameters  = $constructor->getParameters();
        self::assertCount(1, $parameters);
        $parameter = $parameters['pathParams'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);
        self::assertSame('pathParams', $parameter->getName());
        self::assertSame(self::NAMESPACE . '\\PathParams', $parameter->getType());
        self::assertTrue($parameter->isPrivate());
        self::assertTrue($parameter->isReadOnly());

        $getter = $this->getMethod($class, 'getPathParams');
        self::assertSame(self::NAMESPACE . '\\PathParams', $getter->getReturnType());
        self::assertTrue($getter->isPublic());
        self::assertSame('return $this->pathParams;', $getter->getBody());
    }

    public function testGenerateAddsRequestBodyParamAndGetter(): void
    {
        $types         = ['Foo' => '\\Api\\Foo', 'Bar' => '\\Api\\Bar'];
        $className     = self::NAMESPACE . '\\Operation';
        $pointer       = '/paths/foo/get';
        $requestBodies = [
            new RequestBodyModel(
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString($types['Foo']))
            ),
            new RequestBodyModel(
                'application/xml',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString($types['Bar']))
            ),
        ];
        $operation     = new OperationModel($className, $pointer, null, null, null, null, $requestBodies);

        $file      = $this->generator->generate($operation);
        $namespace = $this->getNamespace($file, self::NAMESPACE);

        $uses = $namespace->getUses();
        foreach (array_keys($types) as $use) {
            self::assertArrayHasKey($use, $uses);
        }

        $class = $this->getClass($namespace, 'Operation');

        $constructor = $this->getMethod($class, '__construct');
        $parameters  = $constructor->getParameters();
        self::assertCount(1, $parameters);
        $parameter = $parameters['requestBody'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);
        self::assertSame('requestBody', $parameter->getName());
        self::assertSame(implode('|', $types), $parameter->getType());
        self::assertTrue($parameter->isPrivate());
        self::assertTrue($parameter->isReadOnly());

        $getter = $this->getMethod($class, 'getRequestBody');
        self::assertSame(implode('|', $types), $getter->getReturnType());
        self::assertTrue($getter->isPublic());
        self::assertSame('return $this->requestBody;', $getter->getBody());
    }

    public function testGenerateSetsUnionRequestBodyTypes(): void
    {
        $types         = ['Bar' => '\\Foo\\Bar', 'string' => 'string'];
        $className     = self::NAMESPACE . '\\Operation';
        $pointer       = '/paths/foo/get';
        $requestBodies = [
            new RequestBodyModel(
                'default',
                new UnionProperty(
                    '',
                    '',
                    new PropertyMetadata(),
                    new PropertyValue('foo', []),
                    new ClassString('\\Foo\\Bar'),
                    PropertyType::String
                )
            ),
        ];
        $operation     = new OperationModel($className, $pointer, null, null, null, null, $requestBodies);

        $file      = $this->generator->generate($operation);
        $namespace = $this->getNamespace($file, self::NAMESPACE);

        $uses = $namespace->getUses();
        self::assertArrayHasKey('Bar', $uses);

        $class       = $this->getClass($namespace, 'Operation');
        $constructor = $this->getMethod($class, '__construct');

        $parameters = $constructor->getParameters();
        self::assertCount(1, $parameters);
        $parameter = $parameters['requestBody'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);
        self::assertSame('requestBody', $parameter->getName());
        self::assertSame(implode('|', $types), $parameter->getType());

        $getter = $this->getMethod($class, 'getRequestBody');
        self::assertSame(implode('|', $types), $getter->getReturnType());
    }
}
