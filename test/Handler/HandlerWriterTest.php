<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerGenerator;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandlerWriter::class)]
final class HandlerWriterTest extends TestCase
{
    use GeneratorTrait;

    private WriterInterface&Stub $writer;
    private HandlerWriter $handlerWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer        = self::createStub(WriterInterface::class);
        $this->handlerWriter = new HandlerWriter(new HandlerGenerator(), new HandlerFactoryGenerator(), $this->writer);
    }

    public function testWriteWritesClass(): void
    {
        $written = [];
        $this->writer->method('write')
            ->willReturnCallback(static function (PhpFile $file) use (&$written): void {
                $written[] = $file;
            });

        $className  = __NAMESPACE__ . '\\GetHandler';
        $collection = new HandlerCollection();
        $operation  = new OperationModel('Api\\Operation', '/paths/~1foo/get');
        $collection->add(new HandlerModel('/paths/~1foo/get', $className, $operation));

        $this->handlerWriter->write($collection);

        /**
         * @psalm-suppress MixedArgument Don't know why psalm can't figure out this is an array
         */
        self::assertCount(2, $written);

        /**
         * @psalm-suppress UndefinedInterfaceMethod Now it thinks it's countable :(
         * @psalm-suppress PossiblyInvalidArrayAccess
         */
        $handler = $written[0];
        self::assertInstanceOf(PhpFile::class, $handler);
        $namespace = $this->getNamespace($handler, __NAMESPACE__);
        $classes   = $namespace->getClasses();
        self::assertCount(1, $classes);
        self::assertArrayHasKey('GetHandler', $classes);

        /**
         * @psalm-suppress UndefinedInterfaceMethod Now it thinks it's countable :(
         * @psalm-suppress PossiblyInvalidArrayAccess
         */
        $factory = $written[1];
        self::assertInstanceOf(PhpFile::class, $factory);
        $namespace = $this->getNamespace($factory, __NAMESPACE__);
        $classes   = $namespace->getClasses();
        self::assertCount(1, $classes);
        self::assertArrayHasKey('GetHandlerFactory', $classes);
    }
}
