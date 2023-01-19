<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
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
        $configuration = new Configuration(
            __DIR__,
            'openapi.yml',
            __NAMESPACE__,
            'src',
            __NAMESPACE__,
            'test',
            'Model',
            'Operation',
        );
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
            ]);
        $factory = new OperationCollectionBuilderFactory();

        $actual = $factory($container);
        self::assertInstanceOf(OperationCollectionBuilder::class, $actual);
    }
}
