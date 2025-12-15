<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamerFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(DotSnakeCaseNamerFactory::class)]
final class DotSnakeCaseNamerFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $prefix        = 'pet';
        $expected      = "$prefix.foo.get";
        $configuration = [ConfigProvider::GEN_KEY => ['route-prefix' => $prefix]];
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
            ]);

        $factory  = new DotSnakeCaseNamerFactory();
        $instance = $factory($container);

        $route  = new RouteModel('/paths/~1foo/get', '/foo', 'get', [], [], null, []);
        $actual = $instance->getName($route);
        self::assertSame($expected, $actual);
    }
}
