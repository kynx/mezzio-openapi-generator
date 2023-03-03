<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Application;
use PHPUnit\Framework\TestCase;

use function str_replace;
use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGenerator
 */
final class ConfigProviderGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use HandlerTrait;
    use OperationTrait;

    private const OPERATION_NAMESPACE = 'Api\\Operation';
    private const HANDLER_NAMESPACE   = 'Api\\Handler';

    private ConfigProviderGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ConfigProviderGenerator('Api\\ConfigProvider');
    }

    public function testGenerateReturnsFile(): void
    {
        $expectedInvoke = <<<INVOKE_BODY
        return [
            'mezzio-openapi' => \$this->getOpenApiConfig(),
            'dependencies'   => \$this->getDependencyConfig(),
        ];
        INVOKE_BODY;
        $expectedConfig = <<<CONFIG_BODY
        return [
            'operation-factories' => [
                '/paths/~1bar/get' => BarGetRequestFactory::class,
                '/paths/~1foo/get' => FooGetRequestFactory::class,
            ],
        ];
        CONFIG_BODY;
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedDependencies = <<<DEPENDENCIES_BODY
        return [
            'delegators' => [Application::class => [RouteDelegator::class]],
            'factories' => [FooGetHandler::class => InvokableFactory::class, BarGetHandler::class => InvokableFactory::class],
        ];
        DEPENDENCIES_BODY;
        // phpcs:enable

        $operations = $this->getOperationCollection($this->getOperations());
        $handlers   = $this->getHandlerCollection($this->getHandlers($operations));
        $delegator  = 'Api\\RouteDelegator';

        $file = $this->generator->generate($operations, $handlers, $delegator);

        $namespace        = $this->getNamespace($file, 'Api');
        $class            = $this->getClass($namespace, 'ConfigProvider');
        $invoke           = $this->getMethod($class, '__invoke');
        $getOpenApiConfig = $this->getMethod($class, 'getOpenApiConfig');
        $getDependencies  = $this->getMethod($class, 'getDependencyConfig');

        $expectedUses = [
            'BarGetHandler'         => self::HANDLER_NAMESPACE . '\\Bar\\GetHandler',
            'FooGetHandler'         => self::HANDLER_NAMESPACE . '\\Foo\\GetHandler',
            'BarGetRequestFactory'  => self::OPERATION_NAMESPACE . '\\Bar\\Get\\RequestFactory',
            'FooGetRequestFactory'  => self::OPERATION_NAMESPACE . '\\Foo\\Get\\RequestFactory',
            'OpenApiConfigProvider' => OpenApiConfigProvider::class,
            'InvokableFactory'      => InvokableFactory::class,
            'Application'           => Application::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiConfigProvider::class, $attribute->getName());

        self::assertTrue($invoke->isPublic());
        self::assertSame('array', $invoke->getReturnType());
        self::assertSame($expectedInvoke, $this->normalizeBody($invoke->getBody()));

        self::assertTrue($getOpenApiConfig->isPrivate());
        self::assertSame('array', $getOpenApiConfig->getReturnType());
        self::assertSame($expectedConfig, $this->normalizeBody($getOpenApiConfig->getBody()));

        self::assertTrue($getDependencies->isPrivate());
        self::assertSame('array', $getDependencies->getReturnType());
        self::assertSame($expectedDependencies, $this->normalizeBody($getDependencies->getBody()));
    }

    public function testGenerateSkipsOperationsWithoutParameters(): void
    {
        $expectedConfig = "return ['operation-factories' => []];";

        $operations = $this->getOperationCollection([
            new OperationModel(self::OPERATION_NAMESPACE . '\\Foo\\Get\\Operation', '/paths/~1foo/get'),
        ]);
        $handlers   = $this->getHandlerCollection(
            $this->getHandlers($operations, self::OPERATION_NAMESPACE, self::HANDLER_NAMESPACE)
        );

        $file = $this->generator->generate($operations, $handlers, 'Api\\RouteDelegator');

        $namespace        = $this->getNamespace($file, 'Api');
        $class            = $this->getClass($namespace, 'ConfigProvider');
        $getOpenApiConfig = $this->getMethod($class, 'getOpenApiConfig');

        self::assertSame($expectedConfig, $this->normalizeBody($getOpenApiConfig->getBody()));
    }

    private function normalizeBody(string $body): string
    {
        return str_replace("\t", '    ', trim($body));
    }
}
