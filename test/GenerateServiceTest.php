<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator as ModelPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocator as OperationPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\GenerateService
 */
final class GenerateServiceTest extends TestCase
{
    use HandlerTrait;
    use ModelTrait;
    use OperationTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Asset';
    private const DIR       = __DIR__ . '/Asset';

    /** @var ModelWriterInterface&MockObject */
    private ModelWriterInterface $modelWriter;
    /** @var HydratorWriterInterface&MockObject */
    private HydratorWriterInterface $hydratorWriter;
    /** @var OperationWriterInterface&MockObject */
    private OperationWriterInterface $operationWriter;
    /** @var HandlerWriterInterface&MockObject */
    private HandlerWriterInterface $handlerWriter;
    private GenerateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelWriter     = $this->createMock(ModelWriterInterface::class);
        $this->hydratorWriter  = $this->createMock(HydratorWriterInterface::class);
        $this->operationWriter = $this->createMock(OperationWriterInterface::class);
        $this->handlerWriter   = $this->createMock(HandlerWriterInterface::class);

        $this->service = new GenerateService(
            new OpenApiLocator(new PathsLocator(new ModelPathItemLocator())),
            new OpenApiLocator(new PathsLocator(new OperationPathItemLocator())),
            $this->getModelCollectionBuilder(self::NAMESPACE),
            $this->getOperationCollectionBuilder(self::NAMESPACE),
            new RouteCollectionBuilder(),
            $this->getHandlerCollectionBuilder(self::NAMESPACE),
            $this->modelWriter,
            $this->hydratorWriter,
            $this->operationWriter,
            $this->handlerWriter
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
        $expected  = $this->getOperationCollection();
        $hydrators = HydratorCollection::fromModelCollection($this->getModelCollection());
        $actual    = null;
        $this->operationWriter->method('write')
            ->willReturnCallback(function (OperationCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createOperations($expected, $hydrators);
        self::assertSame($expected, $actual);
    }

    public function testCreateHandlersWritesCollection(): void
    {
        $expected = $this->getHandlerCollection();
        $actual   = null;
        $this->handlerWriter->method('write')
            ->willReturnCallback(function (HandlerCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createHandlers($expected);
        self::assertSame($expected, $actual);
    }

    private function getOpenApi(): OpenApi
    {
        $openApi = Reader::readFromYamlFile(self::DIR . '/generate-service.yaml');
        self::assertTrue($openApi->validate(), implode("\n", $openApi->getErrors()));
        return $openApi;
    }

    private function getModelCollection(): ModelCollection
    {
        $collection = new ModelCollection();
        $collection->add(new ClassModel(self::NAMESPACE . '\\Foo', '/components/schemas/Foo', []));

        return $collection;
    }

    private function getOperationCollection(): OperationCollection
    {
        $collection = new OperationCollection();
        $model      = new OperationModel(
            self::NAMESPACE . '\\Get',
            '/paths/~1foo/get',
            null,
            null,
            null,
            null,
            []
        );
        $collection->add($model);

        return $collection;
    }

    private function getRouteCollection(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add(new RouteModel('/paths/~1foo/get', '/foo', 'get', [], []));

        return $collection;
    }

    private function getHandlerCollection(): HandlerCollection
    {
        $collection = new HandlerCollection();
        $collection->add(new HandlerModel('/paths/~1foo/get', self::NAMESPACE . '\\GetHandler', null));

        return $collection;
    }
}
