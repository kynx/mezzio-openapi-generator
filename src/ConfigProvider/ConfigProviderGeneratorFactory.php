<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigProviderGeneratorFactory
{
    public function __invoke(ContainerInterface $container): ConfigProviderGenerator
    {
        /** @var ConfigArray $config */
        $config      = $container->get('config');
        $openApiFile = $config[ConfigProvider::GEN_KEY]['openapi-file'] ?? '';
        $className   = ($config[ConfigProvider::GEN_KEY]['api-namespace'] ?? '') . '\\ConfigProvider';
        return new ConfigProviderGenerator($openApiFile, $className);
    }
}
