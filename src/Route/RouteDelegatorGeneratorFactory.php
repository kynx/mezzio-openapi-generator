<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteDelegatorGeneratorFactory
{
    public function __invoke(ContainerInterface $container): RouteDelegatorGenerator
    {
        /** @var ConfigArray $config */
        $config    = $container->get('config');
        $className = ($config[ConfigProvider::GEN_KEY]['base-namespace'] ?? '') . '\\RouteDelegator';

        return new RouteDelegatorGenerator(
            new FastRouteConverter(),
            $container->get(NamerInterface::class),
            $className
        );
    }
}
