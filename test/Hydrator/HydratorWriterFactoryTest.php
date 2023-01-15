<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterFactory
 */
final class HydratorWriterFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $generator = new HydratorGenerator([]);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [HydratorGenerator::class, $generator],
                [Writer::class, $this->createStub(WriterInterface::class)],
            ]);

        $factory = new HydratorWriterFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(HydratorWriter::class, $actual);
    }
}
