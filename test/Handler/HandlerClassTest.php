<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass
 */
final class HandlerClassTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $route     = new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op1']));
        $className = '\\Foo';

        $handlerFile = new HandlerClass($className, $route);
        self::assertSame($className, $handlerFile->getClassName());
        self::assertSame($route, $handlerFile->getRoute());
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatches(OpenApiRoute $route, OpenApiRoute $test, bool $expected): void
    {
        $handlerFile = new HandlerClass('\\Foo', $route);
        $actual      = $handlerFile->matches(new HandlerClass('\\Foo', $test));
        self::assertSame($expected, $actual);
    }

    public function matchProvider(): array
    {
        return [
            'path_different'      => [
                new OpenApiRoute('/foo', 'post', new Operation([])),
                new OpenApiRoute('/bar', 'post', new Operation([])),
                false,
            ],
            'method_different'    => [
                new OpenApiRoute('/foo', 'post', new Operation([])),
                new OpenApiRoute('/foo', 'get', new Operation([])),
                false,
            ],
            'op_matches'          => [
                new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op'])),
                new OpenApiRoute('/bar', 'get', new Operation(['operationId' => 'op'])),
                false,
            ],
            'path_method_matches' => [
                new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op'])),
                new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op'])),
                true,
            ],
            'op_different'        => [
                new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op1'])),
                new OpenApiRoute('/foo', 'post', new Operation(['operationId' => 'op2'])),
                true,
            ],
        ];
    }
}
