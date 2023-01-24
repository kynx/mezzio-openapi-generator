<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Writer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteDelegatorWriterFactory
{
    public function __invoke(ContainerInterface $container): RouteDelegatorWriter
    {
        return new RouteDelegatorWriter(
            $container->get(RouteDelegatorGenerator::class),
            $container->get(Writer::class)
        );
    }
}
