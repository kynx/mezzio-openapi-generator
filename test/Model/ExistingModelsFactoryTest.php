<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Configuration
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory
 */
final class ExistingModelsFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $configuratiion = new Configuration(__DIR__, '', __NAMESPACE__, 'foo');
        $container      = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuratiion],
            ]);

        $factory = new ExistingModelsFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ExistingModels::class, $actual);
    }
}
