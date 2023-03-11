<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory
 */
final class ModelCollectionBuilderFactoryTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $configuration = [
            ConfigProvider::GEN_KEY => [
                'model-namespace' => __NAMESPACE__,
            ],
        ];
        $modelsBuilder = $this->getModelsBuilder($this->getPropertiesBuilder());
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
                [ModelsBuilder::class, $modelsBuilder],
            ]);

        $factory = new ModelCollectionBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelCollectionBuilder::class, $actual);
    }
}
