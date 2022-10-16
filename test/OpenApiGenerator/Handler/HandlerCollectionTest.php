<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection
 */
final class HandlerCollectionTest extends TestCase
{
    private HandlerCollection $collection;
    private HandlerClass $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = new HandlerCollection();
        $this->handler    = new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', 'getFoo'));
    }

    public function testAddMatchingThrowsException(): void
    {
        $this->collection->add($this->handler);

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("Handler class '\\FooHandler' already exists");
        $this->collection->add(clone $this->handler);
    }

    public function testAddAddsHandler(): void
    {
        $this->collection->add($this->handler);

        self::assertCount(1, $this->collection);
        $actual = $this->collection->current();
        self::assertSame($this->handler, $actual);
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas(HandlerClass $existing, HandlerClass $handler, bool $expected): void
    {
        $this->collection->add($existing);

        $actual = $this->collection->has($handler);
        self::assertSame($expected, $actual);
    }

    public function hasProvider(): array
    {
        return [
            'class_matches'     => [
                new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', null)),
                new HandlerClass('\\FooHandler', $this->makeRoute('/bar', 'get', 'opId')),
                true,
            ],
            'path_matches'      => [
                new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', null)),
                new HandlerClass('\\BarHandler', $this->makeRoute('/foo', 'get', 'opId')),
                true,
            ],
            'operation_matches' => [
                new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', 'opId')),
                new HandlerClass('\\BarHandler', $this->makeRoute('/var', 'get', 'opId')),
                true,
            ],
            'no_match'          => [
                new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', null)),
                new HandlerClass('\\BarHandler', $this->makeRoute('/bar', 'get', null)),
                false,
            ],
        ];
    }

    public function testCollectionIsIterable(): void
    {
        $handlers = [
            new HandlerClass('\\FooHandler', $this->makeRoute('/foo', 'get', 'getFoo')),
            new HandlerClass('\\BarHandler', $this->makeRoute('/bar', 'get', 'getBar')),
        ];
        foreach ($handlers as $handler) {
            $this->collection->add($handler);
        }

        foreach ($this->collection as $i => $handler) {
            self::assertEquals($handlers[$i], $handler);
        }
    }

    private function makeRoute(string $path, string $method, ?string $operationId): OpenApiRoute
    {
        $operation = $operationId === null ? new Operation([]) : new Operation(['operationId' => $operationId]);
        return new OpenApiRoute($path, $method, $operation);
    }
}
