<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApi\Hydrator\DateTimeImmutableHydrator;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorGeneratorFactory
{
    public function __invoke(ContainerInterface $container): HydratorGenerator
    {
        return new HydratorGenerator(
            [
                DateTimeImmutable::class => DateTimeImmutableHydrator::class,
            ]
        );
    }
}
