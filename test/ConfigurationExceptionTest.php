<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Exception;
use Kynx\Mezzio\OpenApiGenerator\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigurationException
 */
final class ConfigurationExceptionTest extends TestCase
{
    public function testInvalidConfigurationFile(): void
    {
        $file      = '/path/to/openapi-generator.json';
        $expected  = "Invalid configuration in file '$file'";
        $exception = ConfigurationException::invalidConfigurationFile($file);
        self::assertSame($expected, $exception->getMessage());
    }

    public function testInvalidConfiguration(): void
    {
        $file      = '/path/to/openapi-generator.json';
        $message   = 'Type error';
        $expected  = "Cannot parse configuration file '$file': $message";
        $exception = ConfigurationException::invalidConfiguration($file, new Exception($message));
        self::assertSame($expected, $exception->getMessage());
    }
}
