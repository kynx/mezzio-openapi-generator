<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory;
use Symfony\Component\Console\Command\Command;

/**
 * Configuration for the `mezzio-openapi` command
 *
 * **DO NOT** wire into your application! The `mezzio-openapi` command`is designed to run in development only: this
 * configuration will _not_ be needed by generated applications.
 *
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProviderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 * @psalm-type CliConfigArray array{commands: array<string, class-string<Command>>}
 * @psalm-type DependencyConfigArray array{factories: array<class-string, class-string>}
 */
final class ConfigProvider
{
    /**
     * @return array{openapi-cli: CliConfigArray, dependencies: DependencyConfigArray}
     */
    public function __invoke(): array
    {
        return [
            'openapi-cli'  => $this->getCliConfig(),
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * @return CliConfigArray
     */
    private function getCliConfig(): array
    {
        return [
            'commands' => [
                'generate' => GenerateCommand::class,
            ],
        ];
    }

    /**
     * @return DependencyConfigArray
     */
    private function getDependencyConfig(): array
    {
        return [
            'factories' => [
                ExistingModels::class         => ExistingModelsFactory::class,
                GenerateCommand::class        => GenerateCommandFactory::class,
                ModelCollectionBuilder::class => ModelCollectionBuilderFactory::class,
                ModelsBuilder::class          => ModelsBuilderFactory::class,
                ModelWriter::class            => ModelWriterFactory::class,
                PropertiesBuilder::class      => PropertiesBuilderFactory::class,
                Writer::class                 => WriterFactory::class,
            ],
        ];
    }
}
