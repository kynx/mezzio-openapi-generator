<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection
 */
final class HandlerCollectionTest extends TestCase
{
    public function testCollectionIsIterable(): void
    {
        $expected   = $this->getHandlers();
        $collection = new HandlerCollection();
        foreach ($expected as $handlerModel) {
            $collection->add($handlerModel);
        }

        $actual = iterator_to_array($collection);
        self::assertSame($expected, $actual);
    }

    public function testCollectionIsCountable(): void
    {
        $collection = new HandlerCollection();
        foreach ($this->getHandlers() as $handlerModel) {
            $collection->add($handlerModel);
        }

        $actual = $collection->count();
        self::assertSame(2, $actual);
    }

    /**
     * @return list<HandlerModel>
     */
    public function getHandlers(): array
    {
        return [
            new HandlerModel('/paths/~1foo/get', '\\Foo\\GetHandler', null),
            new HandlerModel('/paths/~1bar/get', '\\Bar\\GetHandler', null),
        ];
    }
}
