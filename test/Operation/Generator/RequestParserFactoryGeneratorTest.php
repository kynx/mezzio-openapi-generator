<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestParserFactory;
use Kynx\Mezzio\OpenApi\Operation\RequestBody\MediaTypeMatcher;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_values;
use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserFactoryGenerator
 */
final class RequestParserFactoryGeneratorTest extends TestCase
{
    use GeneratorTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Foo\\Get';

    private RequestParserFactoryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RequestParserFactoryGenerator();
    }

    public function testGeneratorReturnsFactory(): void
    {
        $className   = self::NAMESPACE . '\\Operation';
        $parserClass = self::NAMESPACE . '\\RequestParser';
        $pointer     = '/paths/foo/get';

        $expectedUses = [
            OpenApiRequestParserFactory::class,
            MediaTypeMatcher::class,
            ContainerInterface::class,
        ];
        $expectedBody = 'return new RequestParser($container->get(MediaTypeMatcher::class));';

        $requestBody = new RequestBodyModel(
            'default',
            new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String)
        );
        $operation   = new OperationModel($className, $pointer, null, null, null, null, [$requestBody]);

        $file       = $this->generator->generate($operation);
        $namespace  = $this->getNamespace($file, self::NAMESPACE);
        $uses       = array_values($namespace->getUses());
        $class      = $this->getClass($namespace, 'RequestParserFactory');
        $attributes = $class->getAttributes();
        $invoke     = $this->getMethod($class, '__invoke');
        $parameters = $invoke->getParameters();
        $body       = trim($invoke->getBody());

        self::assertSame($expectedUses, $uses);
        self::assertTrue($class->isFinal());

        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiRequestParserFactory::class, $attribute->getName());
        self::assertSame([$pointer], $attribute->getArguments());

        self::assertSame($parserClass, $invoke->getReturnType());

        self::assertCount(1, $parameters);
        self::assertArrayHasKey('container', $parameters);
        $parameter = $parameters['container'];
        self::assertSame(ContainerInterface::class, $parameter->getType());

        self::assertSame($expectedBody, $body);
    }
}
