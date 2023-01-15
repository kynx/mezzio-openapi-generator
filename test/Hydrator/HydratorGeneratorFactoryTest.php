<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApi\Hydrator\DateTimeImmutableHydrator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGeneratorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGeneratorFactory
 */
final class HydratorGeneratorFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $expected = [
            DateTimeImmutable::class => DateTimeImmutableHydrator::class,
        ];
        $factory  = new HydratorGeneratorFactory();
        $instance = $factory($this->createStub(ContainerInterface::class));
        self::assertInstanceOf(HydratorGenerator::class, $instance);

        $reflection = new ReflectionProperty($instance, 'overrideHydrators');
        $reflection->setAccessible(true);
        $actual = $reflection->getValue($instance);
        self::assertSame($expected, $actual);
    }
}
