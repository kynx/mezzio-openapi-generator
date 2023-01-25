<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigProviderWriter implements ConfigProviderWriterInterface
{
    public function __construct(
        private readonly ConfigProviderGenerator $generator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(
        OperationCollection $operations,
        HandlerCollection $handlers,
        string $delegatorClassName
    ): void {
        $file = $this->generator->generate($operations, $handlers, $delegatorClassName);
        $this->writer->write($file);
    }
}
