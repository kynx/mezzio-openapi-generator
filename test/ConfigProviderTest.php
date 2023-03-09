<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\ConfigProvider
 */
final class ConfigProviderTest extends TestCase
{
    public function testCommandsHaveFactories(): void
    {
        $config    = (new ConfigProvider())();
        $commands  = $config['laminas-cli']['commands'];
        $factories = $config['dependencies']['factories'];

        foreach ($commands as $class) {
            self::assertArrayHasKey($class, $factories);
        }
    }
}
