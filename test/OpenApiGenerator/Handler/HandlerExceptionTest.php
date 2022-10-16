<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;
use Exception;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @uses \Kynx\Mezzio\OpenApi\OpenApiOperation
 * @uses \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException
 */
final class HandlerExceptionTest extends TestCase
{
    public function testInvalidHandlerPath(): void
    {
        $expected = "'/foo' is not a valid path";

        $actual = HandlerException::invalidHandlerPath('/foo');

        self::assertSame($expected, $actual->getMessage());
    }

    public function testHandlerExists(): void
    {
        $expected     = "Handler class '\Foo' already exists";
        $handlerClass = new HandlerClass('\Foo', new OpenApiRoute('/foo', 'get', new Operation([])));

        $actual = HandlerException::handlerExists($handlerClass);

        self::assertSame($expected, $actual->getMessage());
    }

    public function testInvalidOpenApiOperation(): void
    {
        $expected   = "Invalid OpenApiOperation attribute for class '" . self::class . "'";
        $reflection = new ReflectionClass($this);
        $exception  = self::createStub(Exception::class);

        $actual = HandlerException::invalidOpenApiOperation($reflection, $exception);

        self::assertSame($expected, $actual->getMessage());
        self::assertSame($exception, $actual->getPrevious());
    }
}
