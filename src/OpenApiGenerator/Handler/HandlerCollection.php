<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Countable;
use Iterator;
use Kynx\Mezzio\OpenApi\OpenApiOperation;

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
     * Returns new collection class names replaced
     *
     * The returned collection will contain all operations from the original, with class names from matching operations
     * in the `$collection` replacing the originals.
     */
    public function replaceClassNames(HandlerCollection $collection): HandlerCollection
    {
        $merged = new HandlerCollection();

        foreach ($this->handlers as $handler) {
            foreach ($collection->handlers as $new) {
                if ($handler->matches($new->getOperation())) {
                    $handler = new HandlerClass($new->getClassName(), $handler->getOperation());
                    break;
                }
            }
            $merged->add($handler);
        }

        return $merged;
    }

    /**
     * Returns true if collection contains matching handler
     *
     * Handlers match if either the class names are the same, the path and methods are the same or if the operationId
     * is the same.
     */
    public function has(HandlerClass $handler): bool
    {
        $operation = $handler->getOperation();
        foreach ($this->handlers as $existing) {
            if ($existing->getClassName() === $handler->getClassName()) {
                return true;
            }
            if ($existing->matches($operation)) {
                return true;
            }
            if ($existing->getOperation()->getOperationId() === null && $operation->getOperationId() === null) {
                continue;
            }
            if ($existing->getOperation()->getOperationId() === $handler->getOperation()->getOperationId()) {
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

    public function key(): mixed
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