<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute
 */
final class OpenApiRouteTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $path      = '/foo';
        $method    = 'get';
        $operation = new Operation([]);
        $route     = new OpenApiRoute($path, $method, $operation);

        self::assertSame($path, $route->getPath());
        self::assertSame($method, $route->getMethod());
        self::assertSame($operation, $route->getOperation());
    }
}
