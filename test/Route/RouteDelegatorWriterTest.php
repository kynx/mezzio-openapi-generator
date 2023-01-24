<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriter;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriter
 */
final class RouteDelegatorWriterTest extends TestCase
{
    use GeneratorTrait;
    use HandlerTrait;
    use RouteTrait;

    /** @var WriterInterface&MockObject */
    private WriterInterface $writer;
    private RouteDelegatorWriter $routeWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer      = $this->createMock(WriterInterface::class);
        $this->routeWriter = new RouteDelegatorWriter(
            $this->getRouteDelegatorGenerator(__NAMESPACE__),
            $this->writer
        );
    }

    public function testGetDelegatorClassNameReturnsName(): void
    {
        $expected = __NAMESPACE__ . '\RouteDelegator';
        $actual   = $this->routeWriter->getDelegatorClassName();
        self::assertSame($expected, $actual);
    }

    public function testWriteWritesRouteDelegator(): void
    {
        $handlers = $this->getHandlerCollection($this->getHandlers());
        $routes   = $this->getRouteCollection($this->getRoutes());

        $written = null;
        $this->writer->expects(self::once())
            ->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$written) {
                $written = $file;
            });

        $this->routeWriter->write($routes, $handlers);
        self::assertInstanceOf(PhpFile::class, $written);
        $namespace = $this->getNamespace($written, __NAMESPACE__);
        $classes   = $namespace->getClasses();
        self::assertArrayHasKey('RouteDelegator', $classes);
    }
}
