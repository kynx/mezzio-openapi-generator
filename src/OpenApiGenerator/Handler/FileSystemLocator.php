<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use RegexIterator;
use SplFileInfo;
use Throwable;

use function current;
use function str_replace;
use function strlen;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\FileSystemLocatorTest
 */
final class FileSystemLocator implements HandlerLocatorInterface
{
    public function __construct(private string $namespace, private string $path)
    {
    }

    public function create(): HandlerCollection
    {
        $collection = new HandlerCollection();

        foreach ($this->getHandlerFiles() as $handlerFile) {
            $collection->add($handlerFile);
        }

        return $collection;
    }

    private function getHandlerFiles(): array
    {
        $handlers = [];
        $directory = $this->getDirectoryIterator();
        $iterator = new RegexIterator(new RecursiveIteratorIterator($directory), '|\.php$|');

        foreach ($iterator as $file) {
            $reflection = $this->getReflection($file);
            if (! $this->isRequestHandler($reflection)) {
                continue;
            }

            $operation = $this->getOpenApiOperation($reflection);
            if ($operation === null) {
                continue;
            }

            $handlers[] = new HandlerClass($reflection->getName(), $operation);
        }

        return $handlers;
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
        $name = substr(
            $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename('.php'),
            strlen($this->path)
        );
        $className = $this->namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $name);

        try {
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