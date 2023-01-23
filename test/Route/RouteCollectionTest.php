<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection
 */
final class RouteCollectionTest extends TestCase
{
    public function testCollectionIsIterable(): void
    {
        $expected   = $this->getRoutes();
        $collection = new RouteCollection();
        foreach ($expected as $route) {
            $collection->add($route);
        }

        $actual = iterator_to_array($collection);
        self::assertSame($expected, $actual);
    }

    public function testCollectionIsCountable(): void
    {
        $collection = new RouteCollection();
        foreach ($this->getRoutes() as $route) {
            $collection->add($route);
        }

        $actual = $collection->count();
        self::assertSame(2, $actual);
    }

    /**
     * @return list<RouteModel>
     */
    private function getRoutes(): array
    {
        return [
            new RouteModel('/paths/foo', '/foo', 'get', [], []),
            new RouteModel('/paths/foo', '/foo', 'post', [], []),
        ];
    }
}
