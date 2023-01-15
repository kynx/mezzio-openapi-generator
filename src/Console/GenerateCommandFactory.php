<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Console;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\GenerateService;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Console\GenerateCommandFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class GenerateCommandFactory
{
    public function __invoke(ContainerInterface $container): GenerateCommand
    {
        $configuration = $container->get(Configuration::class);

        return new GenerateCommand(
            $configuration->getProjectDir(),
            $configuration->getOpenApiFile(),
            $container->get(GenerateService::class)
        );
    }
}
