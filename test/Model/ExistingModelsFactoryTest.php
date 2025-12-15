<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ExistingModelsFactory::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(ExistingModels::class)]
final class ExistingModelsFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $configuratiion = [
            ConfigProvider::GEN_KEY => [
                'base-namespace' => __NAMESPACE__,
                'src-dir'        => 'foo',
            ],
        ];
        $container      = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuratiion],
            ]);

        $factory = new ExistingModelsFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ExistingModels::class, $actual);
    }
}
