<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ModelWriter implements ModelWriterInterface
{
    public function __construct(
        private readonly ModelGenerator $generator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(ModelCollection $modelCollection): void
    {
        foreach ($modelCollection as $model) {
            $file = $this->generator->generate($model);
            $this->writer->write($file);
        }
    }
}
