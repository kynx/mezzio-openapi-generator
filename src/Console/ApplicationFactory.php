<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Console;

use Composer\InstalledVersions;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Application;

use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Console\ApplicationFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ApplicationFactory
{
    public function __invoke(Configuration $configuration): Application
    {
        $config                                          = (new ConfigProvider())();
        $dependencies                                    = $config['dependencies'];
        $dependencies['services']['config']              = $config;
        $dependencies['services'][$configuration::class] = $configuration;

        /** @psalm-suppress InvalidArgument */
        $container = new ServiceManager($dependencies);

        $version = InstalledVersions::getVersion('kynx/mezzio-openapi-generator');
        assert($version !== null);

        $application = new Application('mezzio-openapi', $version);
        $application->setCommandLoader(new CommandLoader($container, $config['openapi-cli']['commands']));

        return $application;
    }
}
