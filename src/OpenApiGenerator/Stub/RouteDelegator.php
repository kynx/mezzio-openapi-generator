<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Stub;

use Mezzio\Application;
use Psr\Container\ContainerInterface;

final class RouteDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): Application
    {
        $app = $callback();
        assert($app instanceof Application);

        return $app;
    }
}
