<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestFactory;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRouteDelegator;
use Kynx\Mezzio\OpenApi\Middleware\OpenApiOperationMiddleware;
use Kynx\Mezzio\OpenApi\Middleware\ValidationMiddleware;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Mezzio\Application;
use Mezzio\Authentication\AuthenticationMiddleware;
use Nette\PhpGenerator\PhpNamespace;
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

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<INVOKE_BODY
        \$app = \$callback();
        assert(\$app instanceof Application);
        
        \$app->get('$path', [ValidationMiddleware::class, OpenApiOperationMiddleware::class, FooGetHandler::class], 'api.foo.get')->setOptions([
        	OpenApiRequestFactory::class => '$getPointer',
        ]);
        
        \$app->post('$path', [ValidationMiddleware::class, OpenApiOperationMiddleware::class, FooPostHandler::class], 'api.foo.post')->setOptions([
        	OpenApiRequestFactory::class => '$postPointer',
        ]);
        
        return \$app;
        INVOKE_BODY;
        // phpcs:enable

        $get        = new RouteModel("/paths$path/get", $path, 'get', [], [], null, []);
        $post       = new RouteModel("/paths$path/post", $path, 'post', [], [], null, []);
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
            'OpenApiRequestFactory'      => OpenApiRequestFactory::class,
            'OpenApiRouteDelegator'      => OpenApiRouteDelegator::class,
            'OpenApiOperationMiddleware' => OpenApiOperationMiddleware::class,
            'ValidationMiddleware'       => ValidationMiddleware::class,
            'FooGetHandler'              => $getHandler,
            'FooPostHandler'             => $postHandler,
            'Application'                => Application::class,
            'ContainerInterface'         => ContainerInterface::class,
        ];
        $actualUses   = $namespace->getUses();
        self::assertSame($expectedUses, $actualUses);

        $expectedUseFunctions = [
            'assert' => 'assert',
        ];
        $actualUseFunctions   = $namespace->getUses(PhpNamespace::NameFunction);
        self::assertSame($expectedUseFunctions, $actualUseFunctions);

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

    public function testGenerateAddsAuthenticationMiddleware(): void
    {
        $path       = '/pets';
        $pointer    = '/paths/~pets/get';
        $handler    = self::NAMESPACE . "\\Handlers\\Pet\\GetHandler";

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<INVOKE_BODY
        \$app = \$callback();
        assert(\$app instanceof Application);
        
        \$app->get('$path', [
        \tAuthenticationMiddleware::class,
        \tValidationMiddleware::class,
        \tOpenApiOperationMiddleware::class,
        \tPetGetHandler::class,
        ], 'api.pets.get')->setOptions([OpenApiRequestFactory::class => '$pointer']);
        
        return \$app;
        INVOKE_BODY;
        // phpcs:enable

        $security   = $this->createStub(SecurityModelInterface::class);
        $model = new RouteModel($pointer, $path, 'get', [], [], $security, []);
        $collection = new RouteCollection();
        $collection->add($model);

        $map = [
            $pointer  => $handler,
        ];

        $generator = $this->getRouteDelegatorGenerator(self::NAMESPACE);
        $file      = $generator->generate($collection, $map);

        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'RouteDelegator');
        $method    = $this->getMethod($class, '__invoke');

        $uses = $namespace->getUses();
        self::assertArrayHasKey('AuthenticationMiddleware', $uses);

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }

    public function testGenerateAddsExtensionMiddleware(): void
    {
        $path       = '/pets';
        $pointer    = '/paths/~pets/get';
        $middleware = self::NAMESPACE . '\\Middleware\\PetGuard';
        $handler    = self::NAMESPACE . "\\Handlers\\Pet\\GetHandler";

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<INVOKE_BODY
        \$app = \$callback();
        assert(\$app instanceof Application);
        
        \$app->get('$path', [
        \tValidationMiddleware::class,
        \tOpenApiOperationMiddleware::class,
        \tPetGuard::class,
        \tPetGetHandler::class,
        ], 'api.pets.get')->setOptions([OpenApiRequestFactory::class => '$pointer']);
        
        return \$app;
        INVOKE_BODY;
        // phpcs:enable

        $model = new RouteModel($pointer, $path, 'get', [], [], null, [$middleware]);
        $collection = new RouteCollection();
        $collection->add($model);

        $map = [
            $pointer  => $handler,
        ];

        $generator = $this->getRouteDelegatorGenerator(self::NAMESPACE);
        $file      = $generator->generate($collection, $map);

        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'RouteDelegator');
        $method    = $this->getMethod($class, '__invoke');

        $uses = $namespace->getUses();
        self::assertArrayHasKey('PetGuard', $uses);

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }
}
