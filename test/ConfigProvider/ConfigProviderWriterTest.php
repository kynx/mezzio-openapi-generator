<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGenerator;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriter;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriter
 */
final class ConfigProviderWriterTest extends TestCase
{
    use GeneratorTrait;
    use HandlerTrait;
    use OperationTrait;

    public function testWriteWritesFile(): void
    {
        $operations = $this->getOperationCollection($this->getOperations());
        $handlers   = $this->getHandlerCollection($this->getHandlers($operations));
        $generator  = new ConfigProviderGenerator('public/openapi.yaml', 'Api\\ConfigProvider');
        $writer     = $this->createMock(WriterInterface::class);
        $written    = null;
        $writer->method('write')
            ->willReturnCallback(function (PhpFile $file) use (&$written) {
                $written = $file;
            });

        $configProviderWriter = new ConfigProviderWriter($generator, $writer);
        $configProviderWriter->write($operations, $handlers, 'Api\\RouteDelegator');

        self::assertInstanceOf(PhpFile::class, $written);
        $namespace = $this->getNamespace($written, 'Api');
        $classes   = $namespace->getClasses();
        self::assertArrayHasKey('ConfigProvider', $classes);
    }
}
