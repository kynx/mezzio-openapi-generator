<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilderFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionProperty;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilderFactory
 */
final class RouteCollectionBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $expected  = [
            'some-guard' => MiddlewareInterface::class,
        ];
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        ConfigProvider::GEN_KEY => [
                            'extension-middleware' => $expected,
                        ],
                    ],
                ],
            ]);

        $factory  = new RouteCollectionBuilderFactory();
        $instance = $factory($container);

        $reflection = new ReflectionProperty($instance, 'middleware');
        $actual     = $reflection->getValue($instance);
        self::assertSame($expected, $actual);
    }
}
