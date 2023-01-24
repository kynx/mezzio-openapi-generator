<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
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
        $configuration = new Configuration(__DIR__, '', __NAMESPACE__);
        $namer         = $this->createMock(NamerInterface::class);
        $namer->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('named');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
                [NamerInterface::class, $namer],
            ]);

        $factory  = new RouteDelegatorGeneratorFactory();
        $instance = $factory($container);

        $handlerMap = [
            '/paths/~1foo/get' => 'Foo\\GetHandler',
            '/paths/~1bar/get' => 'Bar\\GetHandler',
        ];
        $instance->generate($this->getRouteCollection($this->getRoutes()), $handlerMap);
    }
}
