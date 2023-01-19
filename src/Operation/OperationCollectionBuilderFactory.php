<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): OperationCollectionBuilder
    {
        $configuration = $container->get(Configuration::class);

        $namespace    = $configuration->getBaseNamespace() . '\\' . $configuration->getOperationNamespace();
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);

        $propertyLabeler  = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
        $operationBuilder = new OperationBuilder(new ParameterBuilder($propertyLabeler));

        return new OperationCollectionBuilder($classNamer, $operationBuilder);
    }
}
