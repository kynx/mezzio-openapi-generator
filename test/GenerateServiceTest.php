<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator as ModelPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocator as OperationPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use KynxTest\Mezzio\OpenApiGenerator\Route\RouteTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenerateService::class)]
final class GenerateServiceTest extends TestCase
{
    use HandlerTrait;
    use ModelTrait;
    use OperationTrait;
    use RouteTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Asset';
    private const DIR       = __DIR__ . '/Asset';

    private ModelWriterInterface&Stub $modelWriter;
    private HydratorWriterInterface&Stub $hydratorWriter;
    private OperationWriterInterface&Stub $operationWriter;
    private HandlerWriterInterface&Stub $handlerWriter;
    private RouteDelegatorWriterInterface&Stub $routeDelegatorWriter;
    private ConfigProviderWriterInterface&Stub $configProviderWriter;
    private GenerateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelWriter          = self::createStub(ModelWriterInterface::class);
        $this->hydratorWriter       = self::createStub(HydratorWriterInterface::class);
        $this->operationWriter      = self::createStub(OperationWriterInterface::class);
        $this->routeDelegatorWriter = self::createStub(RouteDelegatorWriterInterface::class);
        $this->handlerWriter        = self::createStub(HandlerWriterInterface::class);
        $this->configProviderWriter = self::createStub(ConfigProviderWriterInterface::class);

        $this->service = new GenerateService(
            new OpenApiLocator(new PathsLocator(new ModelPathItemLocator())),
            new OpenApiLocator(new PathsLocator(new OperationPathItemLocator())),
            $this->getModelCollectionBuilder($this->getPropertiesBuilder(), self::NAMESPACE),
            $this->getOperationCollectionBuilder(self::NAMESPACE),
            new RouteCollectionBuilder([]),
            $this->getHandlerCollectionBuilder(self::NAMESPACE),
            $this->modelWriter,
            $this->hydratorWriter,
            $this->operationWriter,
            $this->routeDelegatorWriter,
            $this->handlerWriter,
            $this->configProviderWriter
        );
    }

    public function testCreateModelsWriteCollection(): void
    {
        $expected = $this->getModelCollection();
        $actual   = null;
        $this->modelWriter->method('write')
            ->willReturnCallback(function (ModelCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createModels($expected);
        self::assertSame($expected, $actual);
    }

    public function testCreateHydratorsWritesCollection(): void
    {
        $expected = HydratorCollection::fromModelCollection($this->getModelCollection());
        $actual   = null;
        $this->hydratorWriter->method('write')
            ->willReturnCallback(function (HydratorCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createHydrators($expected);
        self::assertEquals($expected, $actual);
    }

    public function testCreateOperationsWritesCollection(): void
    {
        $expected  = $this->getOperationCollection($this->getOperations());
        $hydrators = HydratorCollection::fromModelCollection($this->getModelCollection());
        $actual    = null;
        $this->operationWriter->method('write')
            ->willReturnCallback(function (OperationCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createOperations($expected, $hydrators);
        self::assertSame($expected, $actual);
    }

    public function testCreateRouteDelegatorWritesRouteDelegator(): void
    {
        $routes       = $this->getRouteCollection($this->getRoutes());
        $operations   = $this->getOperationCollection($this->getOperations());
        $handlers     = $this->getHandlerCollection($this->getHandlers($operations));
        $actualRoutes = $actualHandlers = null;
        $this->routeDelegatorWriter->method('write')
            ->willReturnCallback(function (
                RouteCollection $routeCollection,
                HandlerCollection $handlerCollection
            ) use (
                &$actualRoutes,
                &$actualHandlers
            ): void {
                $actualRoutes   = $routeCollection;
                $actualHandlers = $handlerCollection;
            });

        $this->service->createRouteDelegator($routes, $handlers);
        self::assertSame($routes, $actualRoutes);
        self::assertSame($handlers, $actualHandlers);
    }

    public function testCreateHandlersWritesCollection(): void
    {
        $operations = $this->getOperationCollection($this->getOperations());
        $expected   = $this->getHandlerCollection($this->getHandlers($operations));
        $actual     = null;
        $this->handlerWriter->method('write')
            ->willReturnCallback(function (HandlerCollection $collection) use (&$actual): void {
                $actual = $collection;
            });

        $this->service->createHandlers($expected);
        self::assertSame($expected, $actual);
    }

    public function testCreateConfigProviderWritesConfigProvider(): void
    {
        $operations       = $this->getOperationCollection($this->getOperations());
        $handlers         = $this->getHandlerCollection($this->getHandlers($operations));
        $actualOperations = $actualHandlers = null;
        $this->configProviderWriter->method('write')
            ->willReturnCallback(
                function (
                    OperationCollection $operationCollection,
                    HandlerCollection $handlerCollection
                ) use (
                    &$actualOperations,
                    &$actualHandlers
                ): void {
                    $actualOperations = $operationCollection;
                    $actualHandlers   = $handlerCollection;
                }
            );

        $this->service->createConfigProvider($operations, $handlers);
        self::assertSame($operations, $actualOperations);
        self::assertSame($handlers, $actualHandlers);
    }

    private function getModelCollection(): ModelCollection
    {
        $collection = new ModelCollection();
        $collection->add(new ClassModel(self::NAMESPACE . '\\Foo', '/components/schemas/Foo', []));

        return $collection;
    }
}
