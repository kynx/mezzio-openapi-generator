<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ModelWriterFactory::class)]
#[UsesClass(MediaTypeLocator::class)]
#[UsesClass(OpenApiLocator::class)]
#[UsesClass(OperationLocator::class)]
#[UsesClass(ParameterLocator::class)]
#[UsesClass(PathItemLocator::class)]
#[UsesClass(PathsLocator::class)]
#[UsesClass(RequestBodyLocator::class)]
#[UsesClass(ResponseLocator::class)]
#[UsesClass(ModelCollectionBuilder::class)]
#[UsesClass(ModelGenerator::class)]
#[UsesClass(ModelWriter::class)]
#[UsesClass(ModelsBuilder::class)]
#[UsesClass(NamespacedNamer::class)]
#[UsesClass(OperationBuilder::class)]
#[UsesClass(PropertiesBuilder::class)]
#[UsesClass(Writer::class)]
final class ModelWriterFactoryTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $collectionBuilder = $this->getModelCollectionBuilder($this->getPropertiesBuilder(), __NAMESPACE__);
        $writer            = new Writer(__NAMESPACE__, __DIR__);
        $container         = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ModelCollectionBuilder::class, $collectionBuilder],
                [Writer::class, $writer],
            ]);

        $factory = new ModelWriterFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(ModelWriter::class, $actual);
    }
}
