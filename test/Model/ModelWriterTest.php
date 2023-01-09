<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function current;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathsLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\WriterException
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter
 */
final class ModelWriterTest extends TestCase
{
    use ModelTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Asset\\Existing';
    private const DIRECTORY = __DIR__ . '/Asset/Existing';

    /** @var WriterInterface&MockObject */
    private WriterInterface $writer;
    private ModelWriter $modelWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = $this->createMock(WriterInterface::class);

        $this->modelWriter = new ModelWriter(
            new OpenApiLocator(),
            $this->getModelCollectionBuilder(self::NAMESPACE),
            new ExistingModels(self::NAMESPACE, self::DIRECTORY),
            new ModelGenerator(),
            $this->writer
        );
    }

    public function testWriteWritesModels(): void
    {
        $openApi = $this->getOpenApi();
        $actual  = null;
        $this->writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$actual) {
                $actual = $file;
            });

        $this->modelWriter->write($openApi);
        self::assertInstanceOf(PhpFile::class, $actual);
        $class = current($actual->getClasses());
        self::assertInstanceOf(ClassType::class, $class);
        self::assertSame('MatchedClass', $class->getName(), "Class not renamed");
    }

    private function getOpenApi(): OpenApi
    {
        return Reader::readFromYamlFile(__DIR__ . '/Asset/model-writer.yaml');
    }
}
