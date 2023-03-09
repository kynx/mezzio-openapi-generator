<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ExistingModelsFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ExistingModelsFactory
{
    public function __invoke(ContainerInterface $container): ExistingModels
    {
        /** @var ConfigArray $config */
        $config = $container->get('config');
        return new ExistingModels(
            $config[ConfigProvider::GEN_KEY]['base-namespace'] ?? '',
            $config[ConfigProvider::GEN_KEY]['src-dir'] ?? ''
        );
    }
}
