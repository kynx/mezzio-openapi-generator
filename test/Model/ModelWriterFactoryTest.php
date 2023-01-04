<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OpenApiLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathsLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Writer
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory
 */
final class ModelWriterFactoryTest extends TestCase
{
    use ModelTrait;

    public function testInvokeReturnsInstance(): void
    {
        $collectionBuilder = $this->getModelCollectionBuilder(__NAMESPACE__);
        $existingModels    = new ExistingModels(__NAMESPACE__, __DIR__);
        $writer            = new Writer(__NAMESPACE__, __DIR__);
        $container         = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ModelCollectionBuilder::class, $collectionBuilder],
                [ExistingModels::class, $existingModels],
                [Writer::class, $writer],
            ]);

        $factory = new ModelWriterFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelWriter::class, $actual);
    }
}
