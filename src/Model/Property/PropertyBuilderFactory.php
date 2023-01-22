<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Psr\Container\ContainerInterface;

final class PropertyBuilderFactory
{
    public function __invoke(ContainerInterface $container): PropertyBuilder
    {
        return new PropertyBuilder($container->get(TypeMapper::class));
    }
}
