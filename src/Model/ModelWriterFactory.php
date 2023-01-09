<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelWriterFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelWriterFactory
{
    public function __invoke(ContainerInterface $container): ModelWriter
    {
        return new ModelWriter(
            new OpenApiLocator(),
            $container->get(ModelCollectionBuilder::class),
            $container->get(ExistingModels::class),
            new ModelGenerator(),
            $container->get(Writer::class)
        );
    }
}
