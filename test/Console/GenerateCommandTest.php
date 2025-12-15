<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceInterface;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function trim;

#[CoversClass(GenerateCommand::class)]
final class GenerateCommandTest extends TestCase
{
    private string $projectDir = __DIR__ . '/Asset';

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function getCommandTester(
        (GenerateServiceInterface&MockObject)|(GenerateServiceInterface&Stub ) $service
    ): CommandTester {
        $command = new GenerateCommand($this->projectDir, 'test.yaml', $service);
        return new CommandTester($command);
    }

    #[DataProvider('specificationArgumentProvider')]
    public function testConfigureSetsSpecificationArgument(array $arguments, string $expected): void
    {
        $actualModels = $actualOperations = $actualRoutes = null;
        $service      = self::createStub(GenerateServiceInterface::class);
        $service->method('getModels')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actualModels): ModelCollection {
                $actualModels = $openApi;
                return new ModelCollection();
            });
        $service->method('getOperations')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actualOperations): OperationCollection {
                $actualOperations = $openApi;
                return new OperationCollection();
            });
        $service->method('getRoutes')
            ->willReturnCallback(function (OpenApi $openApi) use (&$actualRoutes): RouteCollection {
                $actualRoutes = $openApi;
                return new RouteCollection();
            });
        $service->method('getHandlers')
            ->willReturn(new HandlerCollection());

        $exit = $this->getCommandTester($service)->execute($arguments);
        self::assertSame(0, $exit);
        self::assertInstanceOf(OpenApi::class, $actualModels);
        self::assertSame($expected, $actualModels->info->title);
        self::assertSame($actualModels, $actualOperations);
        self::assertSame($actualModels, $actualRoutes);
    }

    /**
     * @return array<string, array{0: array, 1: string}>
     */
    public static function specificationArgumentProvider(): array
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

        $service = self::createStub(GenerateServiceInterface::class);
        $service->method('getModels')
            ->willReturn(new ModelCollection());
        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute(['specification' => 'nonexistent.yaml']);
        self::assertSame(1, $exit);
        $actual = trim($commandTester->getDisplay());
        self::assertSame($expected, $actual);
    }

    public function testExecuteEmptySpecificationOutputsError(): void
    {
        $specFile = $this->projectDir . '/empty.yaml';
        $expected = "Error reading '$specFile':";

        $service = self::createStub(GenerateServiceInterface::class);
        $service->method('getModels')
            ->willReturn(new ModelCollection());
        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute(['specification' => 'empty.yaml']);
        self::assertSame(1, $exit);
        $actual = trim($commandTester->getDisplay());
        self::assertStringStartsWith($expected, $actual);
    }

    public function testExecuteInvalidSpecificationOutputsError(): void
    {
        $expected = "OpenApi is missing required property: paths";

        $service = self::createStub(GenerateServiceInterface::class);
        $service->method('getModels')
            ->willReturn(new ModelCollection());
        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute(['specification' => 'invalid.yaml']);
        self::assertSame(1, $exit);
        $actual = trim($commandTester->getDisplay());
        self::assertStringContainsString($expected, $actual);
    }

    public function testGenerateCreatesModels(): void
    {
        $service           = $this->createMock(GenerateServiceInterface::class);
        [$modelCollection] = $this->configureModels($service);
        $service->expects(self::once())
            ->method('createModels')
            ->with($modelCollection);

        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesHydrators(): void
    {
        $service            = $this->createMock(GenerateServiceInterface::class);
        [$modelCollection]  = $this->configureModels($service);
        $hydratorCollection = HydratorCollection::fromModelCollection($modelCollection);
        $service->expects(self::once())
            ->method('createHydrators')
            ->with($hydratorCollection);

        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesOperations(): void
    {
        $service                 = $this->createMock(GenerateServiceInterface::class);
        [, $operationCollection] = $this->configureModels($service);
        $service->expects(self::once())
            ->method('createOperations')
            ->with($operationCollection);

        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesRouteDelegator(): void
    {
        $service = $this->createMock(GenerateServiceInterface::class);
        // Gets very confused here and wants both 1 space before and 0 space before
        // phpcs:ignore WebimpressCodingStandard.WhiteSpace.CommaSpacing.SpaceBeforeComma
        [, , $routeCollection, $handlerCollection] = $this->configureModels($service);

        $service->expects(self::once())
            ->method('createRouteDelegator')
            ->with($routeCollection, $handlerCollection);

        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    public function testGenerateCreatesHandler(): void
    {
        $service = $this->createMock(GenerateServiceInterface::class);
        // Gets very confused here and wants both 1 space before and 0 space before
        // phpcs:ignore WebimpressCodingStandard.WhiteSpace.CommaSpacing.SpaceBeforeComma
        [, , , $handlerCollection] = $this->configureModels($service);

        $service->method('getModels')
            ->willReturn(new ModelCollection());
        $service->expects(self::once())
            ->method('createHandlers')
            ->with($handlerCollection);

        $commandTester = $this->getCommandTester($service);
        $exit          = $commandTester->execute([]);
        self::assertSame(0, $exit);
    }

    /**
     * @return array{0: ModelCollection, 1: OperationCollection, 2: RouteCollection, 3: HandlerCollection}
     */
    private function configureModels(GenerateServiceInterface&MockObject $service): array
    {
        $modelCollection     = new ModelCollection();
        $operationCollection = new OperationCollection();
        $routeCollection     = new RouteCollection();
        $handlerCollection   = new HandlerCollection();

        $service->method('getModels')
            ->willReturn($modelCollection);
        $service->method('getOperations')
            ->willReturn($operationCollection);
        $service->method('getRoutes')
            ->willReturn($routeCollection);
        $service->method('getHandlers')
            ->willReturn($handlerCollection);

        return [$modelCollection, $operationCollection, $routeCollection, $handlerCollection];
    }
}
