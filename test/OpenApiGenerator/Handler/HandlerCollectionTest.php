<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
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
        $this->handler    = new HandlerClass(
            'FooHandler',
            new OpenApiOperation('getFoo', 'foo', 'get')
        );
    }

    public function testAddMatchingThrowsException(): void
    {
        $this->collection->add($this->handler);

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("Handler class 'FooHandler' already exists");
        $this->collection->add(clone $this->handler);
    }

    public function testAddAddsHandler(): void
    {
        $this->collection->add($this->handler);

        self::assertCount(1, $this->collection);
        $actual = $this->collection->current();
        self::assertSame($this->handler, $actual);
    }

    public function testReplaceClassNamesPreservesOperation(): void
    {
        $this->collection->add($this->handler);

        $new           = new HandlerClass(
            'BarHandler',
            new OpenApiOperation('getBar', 'foo', 'get')
        );
        $newCollection = new HandlerCollection();
        $newCollection->add($new);

        $replaced = $this->collection->replaceClassNames($newCollection);

        self::assertCount(1, $replaced);
        $actual = $replaced->current();
        self::assertSame($new->getClassName(), $actual->getClassName());
        self::assertSame($this->handler->getOperation(), $actual->getOperation());
    }

    public function testReplaceNonMatchingDoesNothing(): void
    {
        $this->collection->add($this->handler);
        $new           = new HandlerClass(
            'FooHandler',
            new OpenApiOperation('getFoo', 'foo', 'post')
        );
        $newCollection = new HandlerCollection();
        $newCollection->add($new);

        $this->collection->replaceClassNames($newCollection);

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
                new HandlerClass('FooHandler', new OpenApiOperation(null, 'foo', 'get')),
                new HandlerClass('FooHandler', new OpenApiOperation('opId', 'bar', 'get')),
                true,
            ],
            'path_matches'      => [
                new HandlerClass('FooHandler', new OpenApiOperation(null, 'foo', 'get')),
                new HandlerClass('BarHandler', new OpenApiOperation('opId', 'foo', 'get')),
                true,
            ],
            'operation_matches' => [
                new HandlerClass('FooHandler', new OpenApiOperation('opId', 'foo', 'get')),
                new HandlerClass('BarHandler', new OpenApiOperation('opId', 'bar', 'get')),
                true,
            ],
            'no_match'          => [
                new HandlerClass('FooHandler', new OpenApiOperation(null, 'foo', 'get')),
                new HandlerClass('BarHandler', new OpenApiOperation(null, 'bar', 'get')),
                false,
            ],
        ];
    }

    public function testCollectionIsIterable(): void
    {
        $handlers = [
            new HandlerClass('FooHandler', new OpenApiOperation('getFoo', 'foo', 'get')),
            new HandlerClass('BarHandler', new OpenApiOperation('getBar', 'bar', 'get')),
        ];
        foreach ($handlers as $handler) {
            $this->collection->add($handler);
        }

        foreach ($this->collection as $i => $handler) {
            self::assertEquals($handlers[$i], $handler);
        }
    }
}
