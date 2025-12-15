<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use function current;

#[CoversClass(HydratorWriter::class)]
final class HydratorWriterTest extends TestCase
{
    private WriterInterface&Stub $writer;
    private HydratorWriter $hydratorWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = self::createStub(WriterInterface::class);

        $this->hydratorWriter = new HydratorWriter(
            new HydratorGenerator([]),
            $this->writer
        );
    }

    public function testWriteWritesHydrators(): void
    {
        $collection = $this->getHydratorCollection();

        $actual = null;
        $this->writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$actual) {
                $actual = $file;
            });

        $this->hydratorWriter->write($collection);
        self::assertInstanceOf(PhpFile::class, $actual);
        $class = current($actual->getClasses());
        self::assertInstanceOf(ClassType::class, $class);
        self::assertSame('FooHydrator', $class->getName());
    }

    private function getHydratorCollection(): HydratorCollection
    {
        $modelCollection = new ModelCollection();
        $modelCollection->add(new ClassModel('Foo', '/components/schemas/Foo', []));
        return HydratorCollection::fromModelCollection($modelCollection);
    }
}
