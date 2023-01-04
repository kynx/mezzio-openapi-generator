<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Nette\PhpGenerator\PhpFile;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
interface WriterInterface
{
    public function write(PhpFile $file): void;
}
