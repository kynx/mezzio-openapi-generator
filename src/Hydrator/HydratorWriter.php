<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorWriter implements HydratorWriterInterface
{
    public function __construct(
        private readonly HydratorGenerator $generator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(HydratorCollection $hydratorCollection): void
    {
        $hydratorMap = $hydratorCollection->getHydratorMap();
        foreach ($hydratorCollection as $model) {
            $file = $this->generator->generate($model, $hydratorMap);
            $this->writer->write($file);
        }
    }
}
