<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ModelsBuilderFactory::class)]
#[UsesClass(ModelsBuilder::class)]
#[UsesClass(PropertiesBuilder::class)]
final class ModelsBuilderFactoryTest extends TestCase
{
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [PropertiesBuilder::class, $this->getPropertiesBuilder()],
            ]);

        $factory = new ModelsBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelsBuilder::class, $actual);
    }
}
