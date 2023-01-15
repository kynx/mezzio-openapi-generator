<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\WriterFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class WriterFactory
{
    public function __invoke(ContainerInterface $container): Writer
    {
        $configuration = $container->get(Configuration::class);
        return new Writer($configuration->getBaseNamespace(), $configuration->getBaseDir());
    }
}
