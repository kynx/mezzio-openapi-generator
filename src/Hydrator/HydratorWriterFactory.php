<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Writer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorWriterFactory
{
    public function __invoke(ContainerInterface $container): HydratorWriter
    {
        return new HydratorWriter(
            $container->get(HydratorGenerator::class),
            $container->get(Writer::class)
        );
    }
}
