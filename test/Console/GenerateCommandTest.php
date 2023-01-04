<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
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
    /** @var ModelWriterInterface&MockObject */
    private ModelWriterInterface $modelWriter;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelWriter   = $this->createMock(ModelWriterInterface::class);
        $command             = new GenerateCommand($this->projectDir, 'test.yaml', $this->modelWriter);
        $this->commandTester = new CommandTester($command);
    }

    public function testConfigureSetsSpecificationDefault(): void
    {
        $actual = null;
        $this->modelWriter->method('write')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actual): void {
                $actual = $openApi;
            });

        $exit = $this->commandTester->execute([]);
        self::assertSame(0, $exit);
        self::assertInstanceOf(OpenApi::class, $actual);
        self::assertSame('Test Yaml', $actual->info->title);
    }

    public function testExecuteUsesSpecificationArgument(): void
    {
        $actual = null;
        $this->modelWriter->method('write')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actual): void {
                $actual = $openApi;
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
}
