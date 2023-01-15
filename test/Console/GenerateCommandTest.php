<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
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

    public function testConfigureSetsSpecificationDefault(): void
    {
        $actual = null;
        $this->service->method('getModels')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actual): ModelCollection {
                $actual = $openApi;
                return new ModelCollection();
            });

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
        self::assertInstanceOf(OpenApi::class, $actual);
        self::assertSame('Test Yaml', $actual->info->title);
    }

    public function testExecuteUsesSpecificationArgument(): void
    {
        $actual = null;
        $this->service->method('getModels')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actual): ModelCollection {
                $actual = $openApi;
                return new ModelCollection();
            });

        $exit = $this->commandTester->execute(['specification' => 'test.json']);
        self::assertSame(0, $exit);
        self::assertInstanceOf(OpenApi::class, $actual);
        self::assertSame('Test Json', $actual->info->title);
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
        $collection = new ModelCollection();
        $this->service->method('getModels')
            ->willReturn($collection);
        $this->service->expects(self::once())
            ->method('createModels')
            ->with($collection);

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesHydrators(): void
    {
        $collection = new ModelCollection();
        $this->service->method('getModels')
            ->willReturn($collection);
        $this->service->expects(self::once())
            ->method('createHydrators')
            ->with($collection);

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
    }
}
