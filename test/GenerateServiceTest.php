<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\GenerateService
 */
final class GenerateServiceTest extends TestCase
{
    use ModelTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Asset';
    private const DIR       = __DIR__ . '/Asset';

    /** @var ModelWriterInterface&MockObject */
    private ModelWriterInterface $modelWriter;
    /** @var HydratorWriterInterface&MockObject */
    private HydratorWriterInterface $hydratorWriter;
    private GenerateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelWriter    = $this->createMock(ModelWriterInterface::class);
        $this->hydratorWriter = $this->createMock(HydratorWriterInterface::class);

        $this->service = new GenerateService(
            new OpenApiLocator(new PathsLocator(new PathItemLocator())),
            $this->getModelCollectionBuilder(self::NAMESPACE),
            new ExistingModels(self::NAMESPACE, self::DIR),
            $this->modelWriter,
            $this->hydratorWriter
        );
    }

    public function testGetModelsRenamesExisting(): void
    {
        $openApi = $this->getOpenApi();

        $models = $this->service->getModels($openApi);
        self::assertCount(1, $models);
        $actual = $models->current();
        self::assertSame(self::NAMESPACE . '\\ExistingModel', $actual->getClassName());
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
        $models   = $this->getModelCollection();
        $expected = HydratorCollection::fromModelCollection($models);
        $actual   = null;
        $this->hydratorWriter->method('write')
            ->willReturnCallback(function (HydratorCollection $collection) use (&$actual) {
                $actual = $collection;
            });

        $this->service->createHydrators($models);
        self::assertEquals($expected, $actual);
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
}
