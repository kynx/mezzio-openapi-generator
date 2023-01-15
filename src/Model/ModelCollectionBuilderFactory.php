<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): ModelCollectionBuilder
    {
        $configuration = $container->get(Configuration::class);

        $namespace    = $configuration->getBaseNamespace() . '\\' . $configuration->getModelNamespace();
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);
        return new ModelCollectionBuilder(
            $classNamer,
            $container->get(ModelsBuilder::class)
        );
    }
}
