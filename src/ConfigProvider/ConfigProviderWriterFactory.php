<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\Writer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigProviderWriterFactory
{
    public function __invoke(ContainerInterface $container): ConfigProviderWriter
    {
        return new ConfigProviderWriter(
            $container->get(ConfigProviderGenerator::class),
            $container->get(Writer::class)
        );
    }
}
