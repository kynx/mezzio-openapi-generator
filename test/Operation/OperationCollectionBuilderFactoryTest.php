<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderFactory
 */
final class OperationCollectionBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $configuration = [
            ConfigProvider::GEN_KEY => [
                'operation-namespace' => __NAMESPACE__ . '\Operation',
            ],
        ];
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
            ]);
        $factory = new OperationCollectionBuilderFactory();

        $actual = $factory($container);
        self::assertInstanceOf(OperationCollectionBuilder::class, $actual);
    }
}
