<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Writer
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\WriterFactory
 */
final class WriterFactoryTest extends TestCase
{
    public function testInvokeReturnsInstance(): void
    {
        $configuration = [ConfigProvider::GEN_KEY => ['project-dir' => __DIR__]];
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
            ]);

        $factory = new WriterFactory();
        $actual  = $factory($container);
        self::assertInstanceOf(Writer::class, $actual);
    }
}
