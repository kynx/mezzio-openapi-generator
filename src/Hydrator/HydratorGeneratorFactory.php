<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorGeneratorFactory
{
    public function __invoke(ContainerInterface $container): HydratorGenerator
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var array<class-string, class-string<HydratorInterface>> $hydrators */
        $hydrators = $config['openapi-gen']['hydrators'] ?? [];

        return new HydratorGenerator($hydrators);
    }
}
