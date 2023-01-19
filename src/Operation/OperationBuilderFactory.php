<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
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
        $propertyLabeler = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
        return new OperationBuilder(new ParameterBuilder($propertyLabeler));
    }
}
