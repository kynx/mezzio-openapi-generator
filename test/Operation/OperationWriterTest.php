<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\ResponseFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use function current;

#[CoversClass(OperationWriter::class)]
final class OperationWriterTest extends TestCase
{
    use OperationTrait;

    private WriterInterface&Stub $writer;
    private OperationWriter $operationWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer          = self::createStub(WriterInterface::class);
        $this->operationWriter = new OperationWriter(
            new ModelGenerator(),
            new HydratorGenerator([]),
            new RequestGenerator(),
            new RequestFactoryGenerator([]),
            new ResponseFactoryGenerator([]),
            $this->writer
        );
    }

    public function testWriteNoRequestParamsWritesResponseFactory(): void
    {
        $expected           = [
            'ResponseFactory',
        ];
        $collection         = $this->getOperationCollection([
            new OperationModel('\\Foo', '/paths/foo/get'),
        ]);
        $hydratorCollection = HydratorCollection::fromModelCollection(new ModelCollection());
        $written            = [];
        $this->configureWriter($written);

        $this->operationWriter->write($collection, $hydratorCollection);

        self::assertSame($expected, $written);
    }

    public function testWriteWritesModels(): void
    {
        $pathParams = $this->getPathParams();
        $modelName  = GeneratorUtil::getClassName($pathParams->getModel()->getClassName());
        $expected   = [
            $modelName,
            $modelName . 'Hydrator',
            'Request',
            'RequestFactory',
            'ResponseFactory',
        ];

        $written = [];
        $this->configureWriter($written);

        $collection         = $this->getOperationCollection([
            new OperationModel('\\Operation', '/paths/foo/get', $pathParams),
        ]);
        $modelCollection    = new ModelCollection();
        $hydratorCollection = HydratorCollection::fromModelCollection($modelCollection);

        $this->operationWriter->write($collection, $hydratorCollection);

        self::assertSame($expected, $written);
    }

    private function configureWriter(array &$written): void
    {
        $this->writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$written) {
                $class = current($file->getClasses());
                self::assertNotFalse($class);
                $written[] = $class->getName();
            });
    }

    /**
     * @param list<OperationModel> $operations
     */
    private function getOperationCollection(array $operations): OperationCollection
    {
        $collection = new OperationCollection();
        foreach ($operations as $operation) {
            $collection->add($operation);
        }

        return $collection;
    }
}
