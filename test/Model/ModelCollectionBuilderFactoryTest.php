<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Configuration
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory
 */
final class ModelCollectionBuilderFactoryTest extends TestCase
{
    use ModelTrait;

    public function testInvokeReturnsInstance(): void
    {
        $configuration    = new Configuration(__DIR__, '', __NAMESPACE__);
        $modelsBuilder    = $this->getModelsBuilder();
        $operationBuilder = $this->getOperationBuilder();
        $container        = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
                [ModelsBuilder::class, $modelsBuilder],
                [OperationBuilder::class, $operationBuilder],
            ]);

        $factory = new ModelCollectionBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelCollectionBuilder::class, $actual);
    }
}
