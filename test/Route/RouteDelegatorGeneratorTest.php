<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiOperationFactory;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRouteDelegator;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Mezzio\Application;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator
 */
final class RouteDelegatorGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use RouteTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Api';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetClassNameReturnsName(): void
    {
        $expected  = self::NAMESPACE . '\\RouteDelegator';
        $generator = $this->getRouteDelegatorGenerator(self::NAMESPACE);
        $actual    = $generator->getClassName();
        self::assertSame($expected, $actual);
    }

    public function testGenerateReturnsFile(): void
    {
        $path        = '/foo';
        $getPointer  = "/paths$path/get";
        $getHandler  = self::NAMESPACE . "\\Handlers\\Foo\\GetHandler";
        $postPointer = "/paths$path/post";
        $postHandler = self::NAMESPACE . "\\Handlers\\Foo\\PostHandler";

        $expected = <<<INVOKE_BODY
        \$app = \$callback();
        assert(\$app instanceof Application);
        
        \$app->get('$path', GetHandler::class, 'api.foo.get')
            ->setOptions([OpenApiOperationFactory::class => '$getPointer']);
        \$app->post('$path', PostHandler::class, 'api.foo.post')
            ->setOptions([OpenApiOperationFactory::class => '$postPointer']);
        
        return \$app;
        INVOKE_BODY;

        $get        = new RouteModel("/paths$path/get", $path, 'get', [], []);
        $post       = new RouteModel("/paths$path/post", $path, 'post', [], []);
        $collection = new RouteCollection();
        $collection->add($get);
        $collection->add($post);

        /** @var array<string, class-string> $map */
        $map = [
            $getPointer  => $getHandler,
            $postPointer => $postHandler,
        ];

        $converter = $this->createMock(ConverterInterface::class);
        $converter->expects(self::once())
            ->method('sort')
            ->willReturnArgument(0);
        $generator = $this->getRouteDelegatorGenerator(self::NAMESPACE, $converter);
        $file      = $generator->generate($collection, $map);

        self::assertTrue($file->hasStrictTypes());

        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'RouteDelegator');
        $method    = $this->getMethod($class, '__invoke');

        $expectedUses = [
            'OpenApiOperationFactory' => OpenApiOperationFactory::class,
            'OpenApiRouteDelegator'   => OpenApiRouteDelegator::class,
            'GetHandler'              => $getHandler,
            'PostHandler'             => $postHandler,
            'Application'             => Application::class,
            'ContainerInterface'      => ContainerInterface::class,
        ];
        $actualUses   = $namespace->getUses();
        self::assertSame($expectedUses, $actualUses);

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiRouteDelegator::class, $attribute->getName());

        $parameters = $method->getParameters();
        self::assertCount(3, $parameters);
        self::assertArrayHasKey('container', $parameters);
        $container = $parameters['container'];
        self::assertSame(ContainerInterface::class, $container->getType());
        self::assertArrayHasKey('serviceName', $parameters);
        $serviceName = $parameters['serviceName'];
        self::assertSame('string', $serviceName->getType());
        self::assertArrayHasKey('callback', $parameters);
        $callback = $parameters['callback'];
        self::assertSame('callable', $callback->getType());

        self::assertSame(Application::class, $method->getReturnType());

        self::assertSame($expected, trim($method->getBody()));
    }
}
