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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function assert;
use function current;
use function is_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter
 */
final class OperationWriterTest extends TestCase
{
    use OperationTrait;

    /** @var WriterInterface&MockObject */
    private WriterInterface $writer;
    private OperationWriter $operationWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer          = $this->createMock(WriterInterface::class);
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
                assert(is_array($written));
                $written[] = current($file->getClasses())->getName();
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
