<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use RegexIterator;
use SplFileInfo;
use Throwable;

use function assert;
use function current;
use function in_array;
use function iterator_to_array;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\ExistingHandlersTest
 */
final class ExistingHandlers
{
    public function __construct(private readonly string $namespace, private readonly string $path)
    {
    }

    public function updateClassNames(HandlerCollection $collection): HandlerCollection
    {
        $updated  = new HandlerCollection();
        $existing = $this->getHandlerClasses();

        foreach (iterator_to_array($collection) as $handlerClass) {
            foreach ($existing as $existingHandler) {
                if ($handlerClass->matches($existingHandler)) {
                    $handlerClass = new HandlerClass($existingHandler->getClassName(), $handlerClass->getRoute());
                    break;
                }
            }
            $updated->add($handlerClass);
        }

        return $updated;
    }

    /**
     * @return list<HandlerClass>
     */
    private function getHandlerClasses(): array
    {
        $handlers = [];
        foreach ($this->getOpenApiOperations() as $className => $operation) {
            $handlers[] = new HandlerClass(
                $className,
                new OpenApiRoute(
                    $operation->getPath(),
                    $operation->getMethod(),
                    new Operation(['operationId' => $operation->getOperationId()])
                )
            );
        }

        return $handlers;
    }

    /**
     * @return array<string, OpenApiOperation>
     */
    private function getOpenApiOperations(): array
    {
        $operations = [];
        $directory  = $this->getDirectoryIterator();
        $iterator   = new RegexIterator(new RecursiveIteratorIterator($directory), '|\.php$|');

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $reflection = $this->getReflection($file);
            if (! $this->isRequestHandler($reflection)) {
                continue;
            }
            assert($reflection !== null);

            $operation = $this->getOpenApiOperation($reflection);
            if ($operation === null) {
                continue;
            }
            $operations[$reflection->getName()] = $operation;
        }

        return $operations;
    }

    private function getDirectoryIterator(): RecursiveDirectoryIterator
    {
        try {
            $directory = new RecursiveDirectoryIterator($this->path);
        } catch (Throwable $e) {
            throw HandlerException::invalidHandlerPath($this->path);
        }

        return $directory;
    }

    private function getReflection(SplFileInfo $file): ?ReflectionClass
    {
        $name      = substr(
            $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename('.php'),
            strlen($this->path)
        );
        $className = $this->namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $name);

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new ReflectionClass($className);
        } catch (Throwable) {
            return null;
        }
    }

    private function isRequestHandler(?ReflectionClass $reflection): bool
    {
        if ($reflection === null) {
            return false;
        }

        if (! in_array(RequestHandlerInterface::class, $reflection->getInterfaceNames())) {
            return false;
        }

        return true;
    }

    private function getOpenApiOperation(ReflectionClass $reflection): ?OpenApiOperation
    {
        $attribute = current($reflection->getAttributes(OpenApiOperation::class));
        if (! $attribute instanceof ReflectionAttribute) {
            return null;
        }

        try {
            return $attribute->newInstance();
        } catch (Throwable $e) {
            throw HandlerException::invalidOpenApiOperation($reflection, $e);
        }
    }
}
