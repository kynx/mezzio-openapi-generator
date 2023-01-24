<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterFactory;
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
        $writer->expects(self::once())
            ->method('write');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Writer::class, $writer],
            ]);

        $factory  = new HandlerWriterFactory();
        $instance = $factory($container);

        $collection = new HandlerCollection();
        $collection->add(new HandlerModel('/paths/~1foo/get', 'Foo\\GetHandler', null));

        $instance->write($collection);
    }
}
