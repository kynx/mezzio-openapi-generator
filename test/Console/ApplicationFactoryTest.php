<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use Composer\InstalledVersions;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Console\ApplicationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\ConfigProvider
 * @uses \Kynx\Mezzio\OpenApiGenerator\Configuration
 * @uses \Kynx\Mezzio\OpenApiGenerator\Console\CommandLoader
 * @uses \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommand
 * @uses \Kynx\Mezzio\OpenApiGenerator\Console\GenerateCommandFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ExistingModelsFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathsLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Writer
 * @uses \Kynx\Mezzio\OpenApiGenerator\WriterFactory
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Console\ApplicationFactory
 */
final class ApplicationFactoryTest extends TestCase
{
    public function testInvokeSetsNameAndVersion(): void
    {
        $expected      = InstalledVersions::getRootPackage()['version'];
        $factory       = new ApplicationFactory();
        $configuration = new Configuration(__DIR__);

        $application = $factory($configuration);
        self::assertSame('mezzio-openapi', $application->getName());
        self::assertSame($expected, $application->getVersion());
    }

    public function testInvokeSetsCommandLoader(): void
    {
        $factory       = new ApplicationFactory();
        $configuration = new Configuration(__DIR__);

        $application = $factory($configuration);
        self::assertTrue($application->has('generate'));
    }
}
