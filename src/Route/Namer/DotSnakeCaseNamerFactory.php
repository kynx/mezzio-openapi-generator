<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-import-type ConfigArray from ConfigProvider
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class DotSnakeCaseNamerFactory
{
    public function __invoke(ContainerInterface $container): DotSnakeCaseNamer
    {
        /** @var ConfigArray $config */
        $config = $container->get('config');
        return new DotSnakeCaseNamer($config[ConfigProvider::GEN_KEY]['route-prefix']);
    }
}
