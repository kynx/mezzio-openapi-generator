<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Exception;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException
 * @uses \Kynx\Mezzio\OpenApi\OpenApiOperation
 * @uses \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass
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
        $expected = "Handler class '\Foo' already exists";
        $handlerClass = new HandlerClass('\Foo', new OpenApiOperation(null, '/foo', 'get'));

        $actual = HandlerException::handlerExists($handlerClass);

        self::assertSame($expected, $actual->getMessage());
    }

    public function testInvalidOpenApiOperation(): void
    {
        $expected = "Invalid OpenApiOperation attribute for class '" . __CLASS__ . "'";
        $reflection = new ReflectionClass($this);
        $exception = self::createStub(Exception::class);

        $actual = HandlerException::invalidOpenApiOperation($reflection, $exception);

        self::assertSame($expected, $actual->getMessage());
        self::assertSame($exception, $actual->getPrevious());
    }
}
