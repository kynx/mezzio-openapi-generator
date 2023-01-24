<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteDelegatorGeneratorFactory
{
    public function __invoke(ContainerInterface $container): RouteDelegatorGenerator
    {
        $configuration = $container->get(Configuration::class);
        $className     = $configuration->getBaseNamespace() . '\\RouteDelegator';

        return new RouteDelegatorGenerator(
            new FastRouteConverter(),
            $container->get(NamerInterface::class),
            $className
        );
    }
}
