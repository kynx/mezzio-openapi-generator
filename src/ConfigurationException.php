<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Laminas\Diactoros\Exception\RuntimeException;
use Throwable;

use function sprintf;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigurationExceptionTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigurationException extends RuntimeException
{
    public static function invalidConfigurationFile(string $file): self
    {
        return new self(sprintf("Invalid configuration in file '%s'", $file));
    }

    public static function invalidConfiguration(string $file, Throwable $throwable): self
    {
        return new self(sprintf("Cannot parse configuration file '%s': %s", $file, $throwable->getMessage()));
    }
}
