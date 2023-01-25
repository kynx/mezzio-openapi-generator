<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriter;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceFactory;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriter;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\GenerateServiceFactory
 */
final class GenerateServiceFactoryTest extends TestCase
{
    use HandlerTrait;
    use ModelTrait;
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ModelCollectionBuilder::class, $this->getModelCollectionBuilder(__NAMESPACE__)],
                [OperationCollectionBuilder::class, $this->getOperationCollectionBuilder(__NAMESPACE__)],
                [RouteCollectionBuilder::class, new RouteCollectionBuilder()],
                [HandlerCollectionBuilder::class, $this->getHandlerCollectionBuilder(__NAMESPACE__)],
                [ModelWriter::class, $this->createStub(ModelWriterInterface::class)],
                [HydratorWriter::class, $this->createStub(HydratorWriterInterface::class)],
                [OperationWriter::class, $this->createStub(OperationWriterInterface::class)],
                [RouteDelegatorWriter::class, $this->createStub(RouteDelegatorWriterInterface::class)],
                [HandlerWriter::class, $this->createStub(HandlerWriterInterface::class)],
                [ConfigProviderWriter::class, $this->createStub(ConfigProviderWriterInterface::class)],
            ]);

        $factory = new GenerateServiceFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(GenerateService::class, $actual);
    }
}
