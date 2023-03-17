<?php // phpcs:disable Generic.Files.LineLength.TooLong


declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use DateTimeImmutable;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApi\Hydrator\DateTimeImmutableHydrator;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGenerator;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorFactory;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriter;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand;
use Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriter;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGeneratorFactory;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriter;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateIntervalMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\UriInterfaceMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UniquePropertyLabelerFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriter;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationWriterFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamerFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorFactory;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriter;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterFactory;
use Symfony\Component\Console\Command\Command;

use function getcwd;

/**
 * phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 * @psalm-type CliConfigArray array{commands: array<string, class-string<Command>>}
 * @psalm-type GenConfigArray array{
 *      project-dir: string,
 *      openapi-file?: string,
 *      src-dir?: string,
 *      test-dir?: string,
 *      base-namespace?: string,
 *      api-namespace?: string,
 *      handler-namespace?: string,
 *      route-prefix: string,
 *      type-mappers: list<class-string<\Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperInterface>>,
 *      hydrators: array<class-string, class-string<\Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface>>
 * }
 * @psalm-type DependencyConfigArray array{factories: array<class-string, class-string>}
 * @psalm-type ConfigArray array{
 *      openapi-gen: GenConfigArray,
 *      laminas-cli: CliConfigArray,
 *      dependencies: DependencyConfigArray
 * }
 * phpcs:enable
 */
final class ConfigProvider
{
    public const GEN_KEY = 'openapi-gen';

    /**
     * @return array{openapi-gen: GenConfigArray, laminas-cli: CliConfigArray, dependencies: DependencyConfigArray}
     */
    public function __invoke(): array
    {
        return [
            self::GEN_KEY  => $this->getGeneratorConfig(),
            'laminas-cli'  => $this->getCliConfig(),
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
                'mezzio:openapi:generate' => GenerateCommand::class,
            ],
        ];
    }

    /**
     * @return GenConfigArray
     */
    private function getGeneratorConfig(): array
    {
        return [
            'project-dir'  => (string) getcwd(),
            'route-prefix' => 'api',
            'type-mappers' => [
                DateIntervalMapper::class,
                DateTimeImmutableMapper::class,
                UriInterfaceMapper::class,
            ],
            'hydrators'    => [
                DateTimeImmutable::class => DateTimeImmutableHydrator::class,
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
                ConfigProviderGenerator::class    => ConfigProviderGeneratorFactory::class,
                ConfigProviderWriter::class       => ConfigProviderWriterFactory::class,
                ExistingModels::class             => ExistingModelsFactory::class,
                GenerateCommand::class            => GenerateCommandFactory::class,
                GenerateService::class            => GenerateServiceFactory::class,
                HandlerCollectionBuilder::class   => HandlerCollectionBuilderFactory::class,
                HandlerWriter::class              => HandlerWriterFactory::class,
                HydratorGenerator::class          => HydratorGeneratorFactory::class,
                HydratorWriter::class             => HydratorWriterFactory::class,
                ModelCollectionBuilder::class     => ModelCollectionBuilderFactory::class,
                ModelsBuilder::class              => ModelsBuilderFactory::class,
                ModelWriter::class                => ModelWriterFactory::class,
                NamerInterface::class             => DotSnakeCaseNamerFactory::class,
                OperationBuilder::class           => OperationBuilderFactory::class,
                OperationCollectionBuilder::class => OperationCollectionBuilderFactory::class,
                OperationWriter::class            => OperationWriterFactory::class,
                ParameterBuilder::class           => ParameterBuilderFactory::class,
                PropertiesBuilder::class          => PropertiesBuilderFactory::class,
                PropertyBuilder::class            => PropertyBuilderFactory::class,
                RequestBodyBuilder::class         => RequestBodyBuilderFactory::class,
                ResponseBuilder::class            => ResponseBuilderFactory::class,
                RouteCollectionBuilder::class     => RouteCollectionBuilderFactory::class,
                RouteDelegatorGenerator::class    => RouteDelegatorGeneratorFactory::class,
                RouteDelegatorWriter::class       => RouteDelegatorWriterFactory::class,
                TypeMapper::class                 => TypeMapperFactory::class,
                UniqueVariableLabeler::class      => UniquePropertyLabelerFactory::class,
                Writer::class                     => WriterFactory::class,
            ],
        ];
    }
}
// phpcs: enable
