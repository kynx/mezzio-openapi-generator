<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerCollectionBuilderFactory
{
    public function __invoke(ContainerInterface $container): HandlerCollectionBuilder
    {
        $configuration = $container->get(Configuration::class);

        $namespace    = $configuration->getBaseNamespace() . '\\' . $configuration->getHandlerNamespace();
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);
        return new HandlerCollectionBuilder($classNamer);
    }
}
