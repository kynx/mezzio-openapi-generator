<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilderFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilderFactory
 */
final class OperationBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory   = new OperationBuilderFactory();

        $actual = $factory($container);
        self::assertInstanceOf(OperationBuilder::class, $actual);
    }
}
