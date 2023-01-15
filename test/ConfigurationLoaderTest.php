<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\ConfigurationLoader;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Configuration
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigurationLoader
 */
final class ConfigurationLoaderTest extends TestCase
{
    public function testLoadReturnsConfiguration(): void
    {
        $dir      = __DIR__ . '/Asset/Config';
        $expected = new Configuration(
            $dir,
            "foo.yml",
            "\\Kynx\\Api",
            "src/Api",
            "\\KynxTest\\Api",
            "test/Api",
            "Model",
            "Operation",
            "Handler"
        );

        $actual = ConfigurationLoader::load($dir);
        self::assertEquals($expected, $actual);
    }

    public function testLoadReturnsDefault(): void
    {
        $dir      = __DIR__ . '/Asset';
        $expected = new Configuration($dir);

        $actual = ConfigurationLoader::load($dir);
        self::assertEquals($expected, $actual);
    }
}
