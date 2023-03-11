<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestBodyBuilderFactory
{
    public function __invoke(ContainerInterface $container): RequestBodyBuilder
    {
        return new RequestBodyBuilder($container->get(PropertyBuilder::class));
    }
}
