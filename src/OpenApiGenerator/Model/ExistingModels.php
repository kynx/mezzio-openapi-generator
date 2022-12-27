<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\OpenApiSchema;
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
use function substr;

use const DIRECTORY_SEPARATOR;

final class ExistingModels
{
    public function __construct(private readonly string $namespace, private readonly string $path)
    {
    }

    public function updateClassNames(ModelCollection $collection): ModelCollection
    {
        $updated  = new ModelCollection();
        $existing = $this->getOpenApiSchemas();

        foreach ($collection as $model) {
            foreach ($existing as $className => $openApiSchema) {
                if ($openApiSchema->getJsonPointer() === $model->getJsonPointer()) {
                    $model = new ClassModel($className, $model->getJsonPointer(), $model->getSchema());
                    break;
                }
            }
            $updated->add($model);
        }

        return $updated;
    }

    /**
     * @return array<string, OpenApiSchema>
     */
    public function getOpenApiSchemas(): array
    {
        $schemas   = [];
        $directory = $this->getDirectoryIterator();
        $iterator  = new RegexIterator(new RecursiveIteratorIterator($directory), '|\.php$|');

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $reflection = $this->getReflection($file);

            if ($reflection === null) {
                continue;
            }

            $openApiSchema = $this->getOpenApiSchema($reflection);
            if ($openApiSchema === null) {
                continue;
            }

            $schemas[$reflection->getName()] = $openApiSchema;
        }

        return $schemas;
    }

    private function getDirectoryIterator(): RecursiveDirectoryIterator
    {
        try {
            $directory = new RecursiveDirectoryIterator($this->path);
        } catch (Throwable) {
            throw ModelException::invalidModelPath($this->path);
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

    private function getOpenApiSchema(ReflectionClass $class): ?OpenApiSchema
    {
        $attribute = current($class->getAttributes(OpenApiSchema::class));
        if (! $attribute instanceof ReflectionAttribute) {
            return null;
        }

        try {
            return $attribute->newInstance();
        } catch (Throwable $e) {
            throw ModelException::invalidOpenApiSchema($class, $e);
        }
    }
}
