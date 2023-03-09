<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactory;
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
        $configuration = [
            ConfigProvider::GEN_KEY => [
                'project-dir'    => __DIR__,
                'base-namespace' => self::NAMESPACE,
            ],
        ];
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
            ]);

        $factory  = new ConfigProviderGeneratorFactory();
        $instance = $factory($container);

        $operations = $this->getOperationCollection($this->getOperations());
        $handlers   = $this->getHandlerCollection($this->getHandlers($operations));

        $file      = $instance->generate($operations, $handlers, self::NAMESPACE . '\\RouteDelegator');
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $classes   = $namespace->getClasses();
        self::assertArrayHasKey('ConfigProvider', $classes);
    }
}
