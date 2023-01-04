<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory
 */
final class PropertiesBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $factory   = new PropertiesBuilderFactory();
        $actual    = $factory($container);
        self::assertInstanceOf(PropertiesBuilder::class, $actual);
    }
}
