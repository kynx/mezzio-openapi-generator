<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceFactory;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\GenerateServiceFactory
 */
final class GenerateServiceFactoryTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ModelCollectionBuilder::class, $this->getModelCollectionBuilder(__NAMESPACE__)],
                [OperationCollectionBuilder::class, $this->getOperationCollectionBuilder(__NAMESPACE__)],
                [ModelWriter::class, $this->createStub(ModelWriterInterface::class)],
                [HydratorWriter::class, $this->createStub(HydratorWriterInterface::class)],
                [OperationWriter::class, $this->createStub(OperationWriterInterface::class)],
            ]);

        $factory = new GenerateServiceFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(GenerateService::class, $actual);
    }
}
