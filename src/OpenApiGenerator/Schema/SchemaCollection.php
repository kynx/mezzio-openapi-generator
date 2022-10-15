<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use Countable;
use Iterator;

use function count;

final class SchemaCollection implements Iterator, Countable
{
    /** @var list<SchemaClass> */
    private array $schemas;
    private int $index;

    public function __construct()
    {
        $this->schemas = [];
        $this->index   = 0;
    }

    public function add(SchemaClass $schemaClass): void
    {
        if ($this->has($schemaClass)) {
            throw SchemaException::schemaExists($schemaClass);
        }

        $this->schemas[] = $schemaClass;
    }

    public function has(SchemaClass $schemaClass): bool
    {
        foreach ($this->schemas as $existing) {
            if ($existing->matches($schemaClass)) {
                return true;
            }
        }
        return false;
    }

    public function current(): SchemaClass
    {
        return $this->schemas[$this->index];
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
        return isset($this->schemas[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function count(): int
    {
        return count($this->schemas);
    }
}
