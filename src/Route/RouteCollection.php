<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Countable;
use Iterator;

use function count;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteCollectionTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteCollection implements Countable, Iterator
{
    /** @var list<RouteModel> */
    private array $members;
    private int $index;

    public function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    public function add(RouteModel $model): void
    {
        $this->members[] = $model;
    }

    public function current(): RouteModel
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
