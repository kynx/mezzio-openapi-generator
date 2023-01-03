<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Command\Configuration;
use Psr\Container\ContainerInterface;

use function assert;

final class ExistingModelsFactory
{
    public function __invoke(ContainerInterface $container): ExistingModels
    {
        $configuration = $container->get(Configuration::class);
        assert($configuration instanceof Configuration);

        return new ExistingModels($configuration->getSourceNamespace(), $configuration->getSourceDir());
    }
}
