<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApi\Hydrator\DateTimeImmutableHydrator;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGeneratorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

#[CoversClass(HydratorGeneratorFactory::class)]
final class HydratorGeneratorFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $expected  = [
            DateTimeImmutable::class => DateTimeImmutableHydrator::class,
        ];
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', [ConfigProvider::GEN_KEY => ['hydrators' => $expected]]],
            ]);

        $factory  = new HydratorGeneratorFactory();
        $instance = $factory($container);
        self::assertInstanceOf(HydratorGenerator::class, $instance);

        $reflection = new ReflectionProperty($instance, 'overrideHydrators');
        $actual     = $reflection->getValue($instance);
        self::assertSame($expected, $actual);
    }
}
