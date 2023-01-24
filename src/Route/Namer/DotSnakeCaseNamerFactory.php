<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Psr\Container\ContainerInterface;

final class DotSnakeCaseNamerFactory
{
    public function __invoke(ContainerInterface $container): DotSnakeCaseNamer
    {
        $configuration = $container->get(Configuration::class);
        return new DotSnakeCaseNamer($configuration->getRoutePrefix());
    }
}
