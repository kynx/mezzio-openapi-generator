<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Configuration
 * @uses \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory
 */
final class GenerateCommandFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $configuration = new Configuration(__DIR__);
        $modelWriter   = $this->createStub(ModelWriterInterface::class);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
                [ModelWriter::class, $modelWriter],
            ]);

        $factory = new GenerateCommandFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(GenerateCommand::class, $actual);
    }
}
