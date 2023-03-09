<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): HandlerCollectionBuilder
    {
        /** @var ConfigArray $config */
        $config       = $container->get('config');
        $namespace    = $config[ConfigProvider::GEN_KEY]['handler-namespace'] ?? '';
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);
        return new HandlerCollectionBuilder($classNamer);
    }
}
