<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationGenerator;
use Kynx\Mezzio\OpenApiGenerator\Writer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationWriterFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationWriterFactory
{
    public function __invoke(ContainerInterface $container): OperationWriter
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var array<class-string, class-string<HydratorInterface>> $hydrators */
        $hydrators = $config['openapi-gen']['hydrators'] ?? [];

        return new OperationWriter(
            new ModelGenerator(),
            $container->get(HydratorGenerator::class),
            new OperationGenerator(),
            new OperationFactoryGenerator($hydrators),
            $container->get(Writer::class)
        );
    }
}
