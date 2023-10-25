<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Countable;
use Iterator;

use function count;

/**
 * @internal
 * @implements Iterator<int, OperationModel>
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationCollectionTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationCollection implements Iterator, Countable
{
    /** @var list<OperationModel> */
    private array $members;
    private int $index;

    public function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    public function add(OperationModel $model): void
    {
        $this->members[] = $model;
    }

    public function current(): OperationModel
    {
        return $this->members[$this->index];
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return isset($this->members[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function count(): int
    {
        return count($this->members);
    }
}
