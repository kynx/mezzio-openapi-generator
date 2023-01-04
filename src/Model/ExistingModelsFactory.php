<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ExistingModelsFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ExistingModelsFactory
{
    public function __invoke(ContainerInterface $container): ExistingModels
    {
        $configuration = $container->get(Configuration::class);
        return new ExistingModels($configuration->getSourceNamespace(), $configuration->getSourceDir());
    }
}
