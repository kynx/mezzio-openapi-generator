<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Console;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Console\GenerateCommandFactoryTest
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class GenerateCommandFactory
{
    public function __invoke(ContainerInterface $container): GenerateCommand
    {
        /** @var ConfigArray $config */
        $config = $container->get('config');
        return new GenerateCommand(
            $config[ConfigProvider::GEN_KEY]['project-dir'],
            $config[ConfigProvider::GEN_KEY]['openapi-file'] ?? '',
            $container->get(GenerateService::class)
        );
    }
}
