<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Countable;
use Iterator;

use function count;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerCollectionTest
 */
final class HandlerCollection implements Iterator, Countable
{
    /** @var list<HandlerClass> */
    private array $handlers;
    private int $index;

    public function __construct()
    {
        $this->handlers = [];
        $this->index    = 0;
    }

    /**
     * Adds HandlerFile to collection
     */
    public function add(HandlerClass $handler): void
    {
        if ($this->has($handler)) {
            throw HandlerException::handlerExists($handler);
        }

        $this->handlers[] = $handler;
    }

    /**
     * Returns true if collection contains matching handler
     *
     * Handlers match if either the class names are the same, the path and methods are the same or if the operationId
     * is the same.
     */
    public function has(HandlerClass $handler): bool
    {
        $operation = $handler->getRoute()->getOperation();
        foreach ($this->handlers as $existing) {
            if ($existing->getClassName() === $handler->getClassName()) {
                return true;
            }
            if ($existing->matches($handler)) {
                return true;
            }

            $existingOp = $existing->getRoute()->getOperation();
            if (empty($existingOp->operationId) && empty($operation->operationId)) {
                return false;
            }
            if ($existingOp->operationId === $operation->operationId) {
                return true;
            }
        }

        return false;
    }

    public function current(): HandlerClass
    {
        return $this->handlers[$this->index];
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
        return isset($this->handlers[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function count(): int
    {
        return count($this->handlers);
    }
}
