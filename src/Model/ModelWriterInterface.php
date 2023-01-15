<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
interface ModelWriterInterface
{
    public function write(ModelCollection $modelCollection): void;
}
