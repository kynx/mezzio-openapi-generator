<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ModelCollectionBuilderFactory::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(ModelCollectionBuilder::class)]
#[UsesClass(ModelsBuilder::class)]
#[UsesClass(NamespacedNamer::class)]
#[UsesClass(OperationBuilder::class)]
#[UsesClass(PropertiesBuilder::class)]
final class ModelCollectionBuilderFactoryTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $configuration = [
            ConfigProvider::GEN_KEY => [
                'api-namespace' => __NAMESPACE__,
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
