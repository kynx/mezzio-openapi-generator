<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory
 */
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
