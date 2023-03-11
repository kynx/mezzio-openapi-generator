<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ParameterBuilderFactory
{
    public function __invoke(ContainerInterface $container): ParameterBuilder
    {
        return new ParameterBuilder(
            $container->get(UniqueVariableLabeler::class),
            $container->get(PropertyBuilder::class)
        );
    }
}
