<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Kynx\Mezzio\OpenApiGenerator\GenerateServiceInterface;
use KynxTest\Mezzio\OpenApiGenerator\Model\ModelTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory
 */
final class GenerateCommandFactoryTest extends TestCase
{
    use ModelTrait;

    public function testInvokeReturnsInstance(): void
    {
        $configuration = [ConfigProvider::GEN_KEY => ['project-dir' => __DIR__]];
        $service       = $this->createStub(GenerateServiceInterface::class);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
                [GenerateService::class, $service],
            ]);

        $factory = new GenerateCommandFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(GenerateCommand::class, $actual);
    }
}
