<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilderFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(PropertyBuilderFactory::class)]
final class PropertyBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TypeMapper::class, new TypeMapper()],
            ]);

        $factory = new PropertyBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(PropertyBuilder::class, $actual);
    }
}
