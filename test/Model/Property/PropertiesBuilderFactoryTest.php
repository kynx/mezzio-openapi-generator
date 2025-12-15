<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(PropertiesBuilderFactory::class)]
#[UsesClass(PropertiesBuilder::class)]
final class PropertiesBuilderFactoryTest extends TestCase
{
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [UniqueVariableLabeler::class, $this->getUniquePropertyLabeler()],
                [PropertyBuilder::class, $this->getPropertyBuilder()],
            ]);

        $factory = new PropertiesBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(PropertiesBuilder::class, $actual);
    }
}
