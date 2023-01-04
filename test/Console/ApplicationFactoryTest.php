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
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OpenApiLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathsLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriter
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactory
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
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
