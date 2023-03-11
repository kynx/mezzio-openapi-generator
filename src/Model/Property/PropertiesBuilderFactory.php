<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class PropertiesBuilderFactory
{
    public function __invoke(ContainerInterface $container): PropertiesBuilder
    {
        return new PropertiesBuilder(
            $container->get(UniqueVariableLabeler::class),
            $container->get(PropertyBuilder::class)
        );
    }
}
