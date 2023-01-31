<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection
 */
final class OperationCollectionTest extends TestCase
{
    public function testCollectionIsIterable(): void
    {
        $expected   = $this->getOperations();
        $collection = new OperationCollection();
        foreach ($expected as $operation) {
            $collection->add($operation);
        }

        $actual = iterator_to_array($collection);
        self::assertSame($expected, $actual);
    }

    public function testCollectionIsCountable(): void
    {
        $collection = new OperationCollection();
        foreach ($this->getOperations() as $operation) {
            $collection->add($operation);
        }

        $actual = $collection->count();
        self::assertSame(2, $actual);
    }

    /**
     * @return list<OperationModel>
     */
    public function getOperations(): array
    {
        return [
            new OperationModel('\\Foo', '/paths/foo/get'),
            new OperationModel('\\Bar', '/paths/bar/get'),
        ];
    }
}
