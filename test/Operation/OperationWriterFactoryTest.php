<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterFactory
 */
final class OperationWriterFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $hydratorGenerator = new HydratorGenerator([]);
        $writer            = $this->createStub(WriterInterface::class);
        $container         = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', ['openapi-gen' => ['hydrators' => []]]],
                [HydratorGenerator::class, $hydratorGenerator],
                [Writer::class, $writer],
            ]);

        $factory  = new OperationWriterFactory();
        $instance = $factory($container);
        self::assertInstanceOf(OperationWriter::class, $instance);
    }
}
