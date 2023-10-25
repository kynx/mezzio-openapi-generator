<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): RouteCollectionBuilder
    {
        /** @var ConfigArray $config */
        $config     = $container->get('config');
        $middleware = $config[ConfigProvider::GEN_KEY]['extension-middleware'] ?? [];
        return new RouteCollectionBuilder($middleware);
    }
}
