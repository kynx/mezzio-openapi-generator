<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory
 */
final class ModelsBuilderFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $propertiesBuilder = new PropertiesBuilder(
            new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix())
        );
        $container         = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [PropertiesBuilder::class, $propertiesBuilder],
            ]);

        $factory = new ModelsBuilderFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelsBuilder::class, $actual);
    }
}
