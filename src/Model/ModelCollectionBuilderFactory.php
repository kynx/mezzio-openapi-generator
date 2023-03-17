<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): ModelCollectionBuilder
    {
        /** @var ConfigArray $config */
        $config       = $container->get('config');
        $namespace    = $config[ConfigProvider::GEN_KEY]['api-namespace'] ?? '';
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);
        return new ModelCollectionBuilder(
            $classNamer,
            $container->get(ModelsBuilder::class)
        );
    }
}
