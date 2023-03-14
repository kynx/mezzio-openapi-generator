<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;

/**
 * @psalm-require-extends \PHPUnit\Framework\TestCase
 */
trait RouteTrait
{
    /**
     * @param ConverterInterface&\PHPUnit\Framework\MockObject\MockObject|null $converter
     */
    protected function getRouteDelegatorGenerator(
        string $namespace = '',
        ConverterInterface|null $converter = null
    ): RouteDelegatorGenerator {
        if ($converter === null) {
            $converter = $this->createStub(ConverterInterface::class);
            $converter->method('sort')
                ->willReturnArgument(0);
        }
        $converter->method('convert')
            ->willReturnCallback(fn (RouteModel $route): string => $route->getPath());

        return new RouteDelegatorGenerator($converter, new DotSnakeCaseNamer('api'), $namespace . '\\RouteDelegator');
    }

    /**
     * @param list<RouteModel> $routes
     */
    protected function getRouteCollection(array $routes): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($routes as $route) {
            $collection->add($route);
        }

        return $collection;
    }

    /**
     * @return list<RouteModel>
     */
    protected function getRoutes(): array
    {
        return [
            new RouteModel('/paths/~1foo/get', '/foo', 'get', [], [], []),
            new RouteModel('/paths/~1bar/get', '/bar', 'get', [], [], []),
        ];
    }
}
