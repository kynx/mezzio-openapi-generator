<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OperationBuilderFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationBuilderFactory
{
    public function __invoke(ContainerInterface $container): OperationBuilder
    {
        return new OperationBuilder(
            $container->get(ParameterBuilder::class),
            $container->get(RequestBodyBuilder::class),
            $container->get(ResponseBuilder::class)
        );
    }
}
