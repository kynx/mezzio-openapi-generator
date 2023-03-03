<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGenerator;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterFactory
 */
final class ConfigProviderWriterFactoryTest extends TestCase
{
    use HandlerTrait;
    use OperationTrait;

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $generator = new ConfigProviderGenerator('public/openapi.yaml', 'Api\\ConfigProvider');
        $writer    = $this->createMock(WriterInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ConfigProviderGenerator::class, $generator],
                [Writer::class, $writer],
            ]);

        $factory  = new ConfigProviderWriterFactory();
        $instance = $factory($container);

        $operations = $this->getOperationCollection($this->getOperations());
        $handlers   = $this->getHandlerCollection($this->getHandlers($operations));
        $writer->expects(self::once())
            ->method('write');

        $instance->write($operations, $handlers, 'Api\\RouteDelegator');
    }
}
