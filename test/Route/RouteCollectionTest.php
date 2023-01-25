<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection
 */
final class RouteCollectionTest extends TestCase
{
    use RouteTrait;

    public function testCollectionIsIterable(): void
    {
        $expected   = $this->getRoutes();
        $collection = $this->getRouteCollection($expected);

        $actual = iterator_to_array($collection);
        self::assertSame($expected, $actual);
    }

    public function testCollectionIsCountable(): void
    {
        $collection = $this->getRouteCollection($this->getRoutes());

        $actual = $collection->count();
        self::assertSame(2, $actual);
    }
}
