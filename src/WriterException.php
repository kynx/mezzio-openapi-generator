<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use RuntimeException;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\WriterExceptionTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class WriterException extends RuntimeException
{
    public static function cannotCreateDirectory(string $directory): self
    {
        return new self("Cannot create directory '$directory'");
    }

    public static function cannotWriteFile(string $path): self
    {
        return new self("Cannot write file '$path'");
    }
}
