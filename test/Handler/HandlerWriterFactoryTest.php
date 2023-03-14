<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterFactory
 */
final class HandlerWriterFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects(self::exactly(2))
            ->method('write');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Writer::class, $writer],
            ]);

        $factory  = new HandlerWriterFactory();
        $instance = $factory($container);

        $collection = new HandlerCollection();
        $operation  = new OperationModel('Foo\\Operation', '/paths/~1foo/get');
        $collection->add(new HandlerModel('/paths/~1foo/get', 'Foo\\GetHandler', $operation));

        $instance->write($collection);
    }
}
