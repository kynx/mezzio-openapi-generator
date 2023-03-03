<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerWriter implements HandlerWriterInterface
{
    public function __construct(
        private readonly HandlerGenerator $generator,
        private readonly HandlerFactoryGenerator $factoryGenerator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(HandlerCollection $handlerCollection): void
    {
        foreach ($handlerCollection as $handlerModel) {
            $file = $this->generator->generate($handlerModel);
            $this->writer->write($file);

            if ($handlerModel->getOperation()->responsesRequireSerialization()) {
                $file = $this->factoryGenerator->generate($handlerModel);
                $this->writer->write($file);
            }
        }
    }
}
