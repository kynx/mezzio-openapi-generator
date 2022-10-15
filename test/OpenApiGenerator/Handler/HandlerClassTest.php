<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass
 */
final class HandlerClassTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $operation = new OpenApiOperation('op1', '/foo', 'post');
        $className = '\\Foo';

        $handlerFile = new HandlerClass($className, $operation);
        self::assertSame($className, $handlerFile->getClassName());
        self::assertSame($operation, $handlerFile->getOperation());
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatches(OpenApiOperation $operation, OpenApiOperation $test, bool $expected): void
    {
        $handlerFile = new HandlerClass('\\Foo', $operation);
        $operation   = $handlerFile->matches($test);
        self::assertSame($expected, $operation);
    }

    public function matchProvider(): array
    {
        return [
            'path_different'      => [
                new OpenApiOperation(null, '/foo', 'post'),
                new OpenApiOperation(null, '/bar', 'post'),
                false,
            ],
            'method_different'    => [
                new OpenApiOperation(null, '/foo', 'post'),
                new OpenApiOperation(null, '/foo', 'get'),
                false,
            ],
            'op_matches'          => [
                new OpenApiOperation('op', '/foo', 'post'),
                new OpenApiOperation('op', '/bar', 'get'),
                false,
            ],
            'path_method_matches' => [
                new OpenApiOperation('op', '/foo', 'post'),
                new OpenApiOperation('op', '/foo', 'post'),
                true,
            ],
            'op_different'        => [
                new OpenApiOperation('op1', '/foo', 'post'),
                new OpenApiOperation('op2', '/foo', 'post'),
                true,
            ],
        ];
    }
}
