<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Countable;
use Iterator;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;

use function count;

/**
 * @internal
 * @implements Iterator<int, HydratorModel>
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorCollection implements Iterator, Countable
{
    /** @var list<HydratorModel> */
    private array $members;
    private int $index;

    private function __construct()
    {
        $this->members = [];
        $this->index   = 0;
    }

    public static function fromModelCollection(ModelCollection $modelCollection): self
    {
        $instance = new self();
        foreach ($modelCollection as $model) {
            if ($model instanceof ClassModel) {
                $instance->members[] = new HydratorModel($model->getClassName() . 'Hydrator', $model);
            }
        }

        return $instance;
    }

    /**
     * @return array<string, string>
     */
    public function getHydratorMap(): array
    {
        $map = [];
        foreach ($this as $hydrator) {
            $map[$hydrator->getModel()->getClassName()] = $hydrator->getClassName();
        }

        return $map;
    }

    public function current(): HydratorModel
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
