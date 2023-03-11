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
final class ResponseBuilderFactory
{
    public function __invoke(ContainerInterface $container): ResponseBuilder
    {
        return new ResponseBuilder($container->get(PropertyBuilder::class));
    }
}
