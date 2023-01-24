<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerGenerator;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter
 */
final class HandlerWriterTest extends TestCase
{
    use GeneratorTrait;

    /** @var WriterInterface&MockObject */
    private WriterInterface $writer;
    private HandlerWriter $handlerWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer        = $this->createMock(WriterInterface::class);
        $this->handlerWriter = new HandlerWriter(new HandlerGenerator(), $this->writer);
    }

    public function testWriteWritesClass(): void
    {
        $written = null;
        $this->writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$written) {
                $written = $file;
            });

        $className  = __NAMESPACE__ . '\\GetHandler';
        $collection = new HandlerCollection();
        $collection->add(new HandlerModel('/paths/~1foo/get', $className, null));

        $this->handlerWriter->write($collection);
        self::assertInstanceOf(PhpFile::class, $written);
        $namespace = $this->getNamespace($written, __NAMESPACE__);
        $classes   = $namespace->getClasses();
        self::assertCount(1, $classes);
        self::assertArrayHasKey('GetHandler', $classes);
    }
}
