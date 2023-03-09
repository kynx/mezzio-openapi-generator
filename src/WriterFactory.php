<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\WriterFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class WriterFactory
{
    public function __invoke(ContainerInterface $container): Writer
    {
        /** @var ConfigArray $config */
        $config = $container->get('config');
        return new Writer(
            $config[ConfigProvider::GEN_KEY]['base-namespace'] ?? '',
            $config[ConfigProvider::GEN_KEY]['src-dir'] ?? '',
        );
    }
}
