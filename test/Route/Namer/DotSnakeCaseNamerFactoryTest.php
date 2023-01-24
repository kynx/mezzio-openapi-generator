<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamerFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamerFactory
 */
final class DotSnakeCaseNamerFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $prefix        = 'pet';
        $expected      = "$prefix.foo.get";
        $configuration = new Configuration(...['projectDir' => __DIR__, 'routePrefix' => $prefix]);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
            ]);

        $factory  = new DotSnakeCaseNamerFactory();
        $instance = $factory($container);

        $route  = new RouteModel('/paths/~1foo/get', '/foo', 'get', [], []);
        $actual = $instance->getName($route);
        self::assertSame($expected, $actual);
    }
}
