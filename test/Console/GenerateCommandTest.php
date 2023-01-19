<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand
 */
final class GenerateCommandTest extends TestCase
{
    private string $projectDir = __DIR__ . '/Asset';
    /** @var GenerateServiceInterface&MockObject */
    private GenerateServiceInterface $service;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service       = $this->createMock(GenerateServiceInterface::class);
        $command             = new GenerateCommand($this->projectDir, 'test.yaml', $this->service);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @dataProvider specificationArgumentProvider
     */
    public function testConfigureSetsSpecificationArgument(array $arguments, string $expected): void
    {
        $actualModels = $actualOperations = null;
        $this->service->method('getModels')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actualModels): ModelCollection {
                $actualModels = $openApi;
                return new ModelCollection();
            });
        $this->service->method('getOperations')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actualOperations): OperationCollection {
                $actualOperations = $openApi;
                return new OperationCollection();
            });

        $exit = $this->commandTester->execute($arguments);
        self::assertSame(0, $exit);
        self::assertInstanceOf(OpenApi::class, $actualModels);
        self::assertSame($expected, $actualModels->info->title);
        self::assertSame($actualModels, $actualOperations);
    }

    public function specificationArgumentProvider(): array
    {
        return [
            'default'   => [[], 'Test Yaml'],
            'test.json' => [['specification' => 'test.json'], 'Test Json'],
        ];
    }

    public function testExecuteNonExistentSpecificationOutputsError(): void
    {
        $specFile = $this->projectDir . '/nonexistent.yaml';
        $expected = "Specification file '$specFile' does not exist.";

        $exit = $this->commandTester->execute(['specification' => 'nonexistent.yaml']);
        self::assertSame(1, $exit);
        $actual = trim($this->commandTester->getDisplay());
        self::assertSame($expected, $actual);
    }

    public function testExecuteEmptySpecificationOutputsError(): void
    {
        $specFile = $this->projectDir . '/empty.yaml';
        $expected = "Error reading '$specFile':";

        $exit = $this->commandTester->execute(['specification' => 'empty.yaml']);
        self::assertSame(1, $exit);
        $actual = trim($this->commandTester->getDisplay());
        self::assertStringStartsWith($expected, $actual);
    }

    public function testGenerateCreatesModels(): void
    {
        $modelCollection     = new ModelCollection();
        $operationCollection = new OperationCollection();
        $this->service->method('getModels')
            ->willReturn($modelCollection);
        $this->service->method('getOperations')
            ->willReturn($operationCollection);
        $this->service->expects(self::once())
            ->method('createModels')
            ->with($modelCollection);

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesHydrators(): void
    {
        $modelCollection     = new ModelCollection();
        $hydratorCollection  = HydratorCollection::fromModelCollection($modelCollection);
        $operationCollection = new OperationCollection();
        $this->service->method('getModels')
            ->willReturn($modelCollection);
        $this->service->method('getOperations')
            ->willReturn($operationCollection);
        $this->service->expects(self::once())
            ->method('createHydrators')
            ->with($hydratorCollection);

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesOperations(): void
    {
        $modelCollection     = new ModelCollection();
        $operationCollection = new OperationCollection();
        $this->service->method('getModels')
            ->willReturn($modelCollection);
        $this->service->method('getOperations')
            ->willReturn($operationCollection);
        $this->service->expects(self::once())
            ->method('createOperations')
            ->with($operationCollection);

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
    }
}
