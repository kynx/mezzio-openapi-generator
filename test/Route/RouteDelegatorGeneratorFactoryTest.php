<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorFactory
 */
final class RouteDelegatorGeneratorFactoryTest extends TestCase
{
    use RouteTrait;

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $configuration = [
            ConfigProvider::GEN_KEY => [
                'base-namespace' => __NAMESPACE__,
            ],
        ];
        $namer         = $this->createMock(NamerInterface::class);
        $namer->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('named');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
                [NamerInterface::class, $namer],
            ]);

        $factory  = new RouteDelegatorGeneratorFactory();
        $instance = $factory($container);

        $handlerMap = [
            '/paths/~1foo/get' => __NAMESPACE__ . '\\Foo\\GetHandler',
            '/paths/~1bar/get' => __NAMESPACE__ . '\\Bar\\GetHandler',
        ];
        $instance->generate($this->getRouteCollection($this->getRoutes()), $handlerMap);
    }
}
