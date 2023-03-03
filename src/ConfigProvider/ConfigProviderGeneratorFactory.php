<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigProviderGeneratorFactory
{
    public function __invoke(ContainerInterface $container): ConfigProviderGenerator
    {
        $configuration = $container->get(Configuration::class);
        $openApiFile   = $configuration->getOpenApiFile();
        $className     = $configuration->getBaseNamespace() . '\\ConfigProvider';
        return new ConfigProviderGenerator($openApiFile, $className);
    }
}
