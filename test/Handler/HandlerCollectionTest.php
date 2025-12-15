<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

#[CoversClass(HandlerCollection::class)]
final class HandlerCollectionTest extends TestCase
{
    use HandlerTrait;
    use OperationTrait;

    public function testCollectionIsIterable(): void
    {
        $expected   = $this->getHandlers($this->getOperationCollection($this->getOperations()));
        $collection = $this->getHandlerCollection($expected);

        $actual = iterator_to_array($collection);
        self::assertSame($expected, $actual);
    }

    public function testCollectionIsCountable(): void
    {
        $handlers   = $this->getHandlers($this->getOperationCollection($this->getOperations()));
        $collection = $this->getHandlerCollection($handlers);

        $actual = $collection->count();
        self::assertSame(2, $actual);
    }

    public function testGetHandlerMapReturnsMap(): void
    {
        $expected = [
            '/paths/~1foo/get' => 'Api\Handler\Foo\GetHandler',
            '/paths/~1bar/get' => 'Api\Handler\Bar\GetHandler',
        ];

        $handlers   = $this->getHandlers($this->getOperationCollection($this->getOperations()));
        $collection = $this->getHandlerCollection($handlers);

        $actual = $collection->getHandlerMap();
        self::assertSame($expected, $actual);
    }
}
