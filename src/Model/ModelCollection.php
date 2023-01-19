<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Countable;
use Iterator;

use function count;

final class ModelCollection implements Iterator, Countable
{
    /** @var list<AbstractClassLikeModel|EnumModel> */
    private array $members;
    private int $index;

    public function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    /**
     * @return array<string, string>
     */
    public function getClassMap(): array
    {
        $map = [];
        foreach ($this->members as $model) {
            $map[$model->getJsonPointer()] = $model->getClassName();
        }

        return $map;
    }

    public function add(AbstractClassLikeModel|EnumModel $member): void
    {
        if ($this->has($member)) {
            throw ModelException::modelExists($member);
        }

        $this->members[] = $member;
    }

    public function has(AbstractClassLikeModel|EnumModel $schemaClass): bool
    {
        foreach ($this->members as $existing) {
            if ($existing->matches($schemaClass)) {
                return true;
            }
        }
        return false;
    }

    public function current(): AbstractClassLikeModel|EnumModel
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
