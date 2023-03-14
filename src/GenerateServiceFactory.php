<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriter;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator as ModelPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocator as OperationPathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriter;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use Psr\Container\ContainerInterface;

final class GenerateServiceFactory
{
    public function __invoke(ContainerInterface $container): GenerateService
    {
        return new GenerateService(
            new OpenApiLocator(new PathsLocator(new ModelPathItemLocator())),
            new OpenApiLocator(new PathsLocator(new OperationPathItemLocator())),
            $container->get(ModelCollectionBuilder::class),
            $container->get(OperationCollectionBuilder::class),
            $container->get(RouteCollectionBuilder::class),
            $container->get(HandlerCollectionBuilder::class),
            $container->get(ModelWriter::class),
            $container->get(HydratorWriter::class),
            $container->get(OperationWriter::class),
            $container->get(RouteDelegatorWriter::class),
            $container->get(HandlerWriter::class),
            $container->get(ConfigProviderWriter::class)
        );
    }
}
