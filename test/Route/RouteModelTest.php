<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteModel
 */
final class RouteModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $jsonPointer = '/paths/foo';
        $path        = '/foo';
        $method      = 'get';
        $pathParams  = [
            new ParameterModel('id', false, 'string', 'simple', false),
        ];
        $queryParams = [
            new ParameterModel('age', false, 'integer', 'form', true),
        ];
        $security    = $this->createStub(SecurityModelInterface::class);
        $middleware  = [
            self::class,
        ];

        $route = new RouteModel($jsonPointer, $path, $method, $pathParams, $queryParams, $security, $middleware);

        self::assertSame($jsonPointer, $route->getJsonPointer());
        self::assertSame($path, $route->getPath());
        self::assertSame($method, $route->getMethod());
        self::assertSame($pathParams, $route->getPathParams());
        self::assertSame($queryParams, $route->getQueryParams());
        self::assertSame($security, $route->getSecurityModel());
        self::assertSame($middleware, $route->getMiddleware());
    }
}
