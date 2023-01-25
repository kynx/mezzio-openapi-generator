<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactory;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactory
 */
final class ConfigProviderGeneratorFactoryTest extends TestCase
{
    use GeneratorTrait;
    use HandlerTrait;
    use OperationTrait;

    private const NAMESPACE = 'Api';

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $configuration = new Configuration(__DIR__, '', self::NAMESPACE);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
            ]);

        $factory  = new ConfigProviderGeneratorFactory();
        $instance = $factory($container);

        $operations = $this->getOperationCollection($this->getOperations(self::NAMESPACE . '\\Operation'));
        $handlers   = $this->getHandlerCollection($this->getHandlers(self::NAMESPACE . '\\Handler'));

        $file      = $instance->generate($operations, $handlers, self::NAMESPACE . '\\RouteDelegator');
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $classes   = $namespace->getClasses();
        self::assertArrayHasKey('ConfigProvider', $classes);
    }
}
