<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilderFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilderFactory
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
