<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(RouteDelegatorWriterFactory::class)]
final class RouteDelegatorWriterFactoryTest extends TestCase
{
    use GeneratorTrait;
    use HandlerTrait;
    use OperationTrait;
    use RouteTrait;

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $delegator = $this->getRouteDelegatorGenerator(__NAMESPACE__);
        $writer    = $this->createMock(WriterInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [RouteDelegatorGenerator::class, $delegator],
                [Writer::class, $writer],
            ]);

        $factory  = new RouteDelegatorWriterFactory();
        $instance = $factory($container);

        $written = null;
        $writer->expects(self::once())
            ->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$written) {
                $written = $file;
            });

        $routes     = $this->getRouteCollection($this->getRoutes());
        $operations = $this->getOperationCollection($this->getOperations());
        $handlers   = $this->getHandlerCollection($this->getHandlers($operations));
        $instance->write($routes, $handlers);

        self::assertInstanceOf(PhpFile::class, $written);
        $namespace = $this->getNamespace($written, __NAMESPACE__);
        $classes   = $namespace->getClasses();
        self::assertArrayHasKey('RouteDelegator', $classes);
    }
}
