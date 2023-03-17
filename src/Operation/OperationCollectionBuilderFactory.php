<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): OperationCollectionBuilder
    {
        /** @var ConfigArray $config */
        $config       = $container->get('config');
        $namespace    = ($config[ConfigProvider::GEN_KEY]['api-namespace'] ?? '') . '\\Operation';
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);

        return new OperationCollectionBuilder($classNamer, $container->get(OperationBuilder::class));
    }
}
