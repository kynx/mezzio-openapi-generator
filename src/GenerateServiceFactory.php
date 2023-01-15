<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use Psr\Container\ContainerInterface;

final class GenerateServiceFactory
{
    public function __invoke(ContainerInterface $container): GenerateService
    {
        return new GenerateService(
            new OpenApiLocator(new PathsLocator(new PathItemLocator())),
            $container->get(ModelCollectionBuilder::class),
            $container->get(ExistingModels::class),
            $container->get(ModelWriter::class),
            $container->get(HydratorWriter::class)
        );
    }
}
