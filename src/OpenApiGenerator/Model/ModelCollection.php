<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Countable;
use Iterator;

use function count;

/**
 * @internal
 */
final class ModelCollection implements Iterator, Countable
{
    /** @var list<ClassModel|EnumModel|InterfaceModel> */
    private array $members;
    private int $index;

    public function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    public function add(ClassModel|EnumModel|InterfaceModel $member): void
    {
        if ($this->has($member)) {
            throw ModelException::schemaExists($member);
        }

        $this->members[] = $member;
    }

    public function has(ClassModel|EnumModel|InterfaceModel $schemaClass): bool
    {
        foreach ($this->members as $existing) {
            if ($existing->matches($schemaClass)) {
                return true;
            }
        }
        return false;
    }

    public function current(): ClassModel|EnumModel|InterfaceModel
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
