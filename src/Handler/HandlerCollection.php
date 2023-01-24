<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Countable;
use Iterator;

use function count;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerCollectionTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerCollection implements Countable, Iterator
{
    /** @var list<HandlerModel> */
    private array $members;
    private int $index;

    public function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    public function add(HandlerModel $handler): void
    {
        $this->members[] = $handler;
    }

    public function current(): HandlerModel
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
