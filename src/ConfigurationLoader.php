<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\ConfigurationException;
use Throwable;

use function dirname;
use function file_exists;
use function file_get_contents;
use function is_array;
use function json_decode;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigurationLoaderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigurationLoader
{
    private const FILENAME = 'openapi-generator.json';

    public static function load(string $installPath): Configuration
    {
        $file = $installPath . '/' . self::FILENAME;
        if (file_exists($file)) {
            return self::loadFromFile($file);
        }

        // fixme: guess default config from composer.json

        return new Configuration($installPath);
    }

    private static function loadFromFile(string $file): Configuration
    {
        $config = json_decode(file_get_contents($file), true);
        if (! is_array($config)) {
            throw ConfigurationException::invalidConfigurationFile($file);
        }

        /** @var array<string, string> $config */
        $config['projectDir'] = dirname($file);
        try {
            return new Configuration(...$config);
        } catch (Throwable $exception) {
            throw ConfigurationException::invalidConfiguration($file, $exception);
        }
    }
}
