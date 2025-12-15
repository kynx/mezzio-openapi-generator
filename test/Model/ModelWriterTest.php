<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use Kynx\Mezzio\OpenApiGenerator\WriterException;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use function current;

#[CoversClass(ModelWriter::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(ClassModel::class)]
#[UsesClass(AbstractGenerator::class)]
#[UsesClass(ClassGenerator::class)]
#[UsesClass(MediaTypeLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(OpenApiLocator::class)]
#[UsesClass(OperationLocator::class)]
#[UsesClass(ParameterLocator::class)]
#[UsesClass(PathItemLocator::class)]
#[UsesClass(PathsLocator::class)]
#[UsesClass(RequestBodyLocator::class)]
#[UsesClass(ResponseLocator::class)]
#[UsesClass(SchemaLocator::class)]
#[UsesClass(ModelCollection::class)]
#[UsesClass(ModelCollectionBuilder::class)]
#[UsesClass(ModelGenerator::class)]
#[UsesClass(ModelUtil::class)]
#[UsesClass(ModelsBuilder::class)]
#[UsesClass(NamespacedNamer::class)]
#[UsesClass(OperationBuilder::class)]
#[UsesClass(AbstractProperty::class)]
#[UsesClass(PropertiesBuilder::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(PropertyType::class)]
#[UsesClass(SimpleProperty::class)]
#[UsesClass(RouteUtil::class)]
#[UsesClass(WriterException::class)]
final class ModelWriterTest extends TestCase
{
    use ModelTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Asset\\Existing';
    private const DIRECTORY = __DIR__ . '/Asset/Existing';

    private WriterInterface&Stub $writer;
    private ModelWriter $modelWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = self::createStub(WriterInterface::class);

        $this->modelWriter = new ModelWriter(
            new ModelGenerator(),
            $this->writer
        );
    }

    public function testWriteWritesModels(): void
    {
        $collection = $this->getModelCollection();
        $actual     = null;
        $this->writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$actual) {
                $actual = $file;
            });

        $this->modelWriter->write($collection);
        self::assertInstanceOf(PhpFile::class, $actual);
        $class = current($actual->getClasses());
        self::assertInstanceOf(ClassType::class, $class);
        self::assertSame('FooClass', $class->getName());
    }

    private function getModelCollection(): ModelCollection
    {
        $collection = new ModelCollection();
        $collection->add(new ClassModel('FooClass', '/components/schemas/FooClass', []));
        return $collection;
    }
}
