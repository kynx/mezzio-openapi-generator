<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use RegexIterator;
use SplFileInfo;
use Throwable;

use function array_map;
use function assert;
use function current;
use function is_dir;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ExistingModelsTest
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 * @psalm-type ExistingArray array<'class'|'enum'|'interface', array<string, OpenApiModel>>
 */
final class ExistingModels
{
    public function __construct(private readonly string $namespace, private readonly string $path)
    {
    }

    public function updateClassNames(ModelCollection $collection): ModelCollection
    {
        if (! is_dir($this->path)) {
            return $collection;
        }

        $updated  = new ModelCollection();
        $existing = $this->getOpenApiModels();
        $renames  = $this->getRenames($collection, $existing);

        foreach ($collection as $model) {
            $renamed = $this->getRenamedModel($model, $existing, $renames);
            $updated->add($renamed);
        }

        return $updated;
    }

    /**
     * @return ExistingArray
     */
    private function getOpenApiModels(): array
    {
        $schemas   = [
            'class'     => [],
            'enum'      => [],
            'interface' => [],
        ];
        $directory = $this->getDirectoryIterator();
        $iterator  = new RegexIterator(new RecursiveIteratorIterator($directory), '|\.php$|');

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $reflection = $this->getReflection($file);

            if ($reflection === null) {
                continue;
            }

            $openApiModel = $this->getOpenApiModel($reflection);
            if ($openApiModel === null) {
                continue;
            }

            $type = 'class';
            if ($reflection->isEnum()) {
                $type = 'enum';
            } elseif ($reflection->isInterface()) {
                $type = 'interface';
            }

            $schemas[$type][$reflection->getName()] = $openApiModel;
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
        $name = substr(
            $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename('.php'),
            strlen($this->path)
        );
        /** @var class-string $className */
        $className = $this->namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $name);

        try {
            return new ReflectionClass($className);
        } catch (Throwable) {
            return null;
        }
    }

    private function getOpenApiModel(ReflectionClass $class): ?OpenApiModel
    {
        $attribute = current($class->getAttributes(OpenApiModel::class));
        if (! $attribute instanceof ReflectionAttribute) {
            return null;
        }

        try {
            return $attribute->newInstance();
        } catch (Throwable $e) {
            throw ModelException::invalidOpenApiSchema($class, $e);
        }
    }

    /**
     * @param ExistingArray $existing
     * @return array<string, string>
     */
    private function getRenames(ModelCollection $collection, array $existing): array
    {
        $renamed = [];
        foreach ($collection as $model) {
            $type = $this->getType($model);
            foreach ($existing[$type] as $className => $openApiModel) {
                if ($openApiModel->getJsonPointer() === $model->getJsonPointer()) {
                    $renamed[$model->getClassName()] = $className;
                }
            }
        }

        return $renamed;
    }

    /**
     * @param ExistingArray $existing
     * @param array<string, string> $renames
     */
    private function getRenamedModel(
        AbstractClassLikeModel|EnumModel $model,
        array $existing,
        array $renames
    ): AbstractClassLikeModel|EnumModel {
        $className = $this->getExistingName($model, $existing);
        if ($className === null) {
            return $model;
        }

        if ($model instanceof EnumModel) {
            return new EnumModel($className, $model->getJsonPointer(), ...$model->getCases());
        }

        $properties = $this->getRenamedProperties($model, $renames);
        if ($model instanceof InterfaceModel) {
            return new InterfaceModel($className, $model->getJsonPointer(), ...$properties);
        }

        assert($model instanceof ClassModel);
        $implements = array_map(fn (string $orig): string => $renames[$orig] ?? $orig, $model->getImplements());
        return new ClassModel(
            $className,
            $model->getJsonPointer(),
            $implements,
            ...$properties
        );
    }

    /**
     * @param ExistingArray $existing
     */
    private function getExistingName(AbstractClassLikeModel|EnumModel $model, array $existing): string|null
    {
        $type = $this->getType($model);
        foreach ($existing[$type] as $className => $schema) {
            if ($schema->getJsonPointer() === $model->getJsonPointer()) {
                return $className;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $renames
     * @return list<PropertyInterface>
     */
    private function getRenamedProperties(AbstractClassLikeModel $model, array $renames): array
    {
        $properties = [];
        foreach ($model->getProperties() as $property) {
            if ($property instanceof ArrayProperty && $property->getType() instanceof ClassString) {
                $className    = $property->getType()->getClassString();
                $type         = new ClassString($renames[$className] ?? $className, $property->getType()->isEnum());
                $properties[] = new ArrayProperty(
                    $property->getName(),
                    $property->getOriginalName(),
                    $property->getMetadata(),
                    $property->isList(),
                    $type
                );
            } elseif ($property instanceof SimpleProperty && ! $property->getType() instanceof PropertyType) {
                $className    = $property->getType()->getClassString();
                $type         = new ClassString($renames[$className] ?? $className, $property->getType()->isEnum());
                $properties[] = new SimpleProperty(
                    $property->getName(),
                    $property->getOriginalName(),
                    $property->getMetadata(),
                    $type
                );
            } elseif ($property instanceof UnionProperty) {
                $members = [];
                foreach ($property->getMembers() as $member) {
                    if ($member instanceof PropertyType) {
                        $members[] = $member;
                    } else {
                        $className = $member->getClassString();
                        $members[] = new ClassString($renames[$className] ?? $className, $member->isEnum());
                    }
                }
                $properties[] = new UnionProperty(
                    $property->getName(),
                    $property->getOriginalName(),
                    $property->getMetadata(),
                    $property->getDiscriminator(),
                    ...$members
                );
            } else {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    private function getType(AbstractClassLikeModel|EnumModel $model): string
    {
        if ($model instanceof ClassModel) {
            return 'class';
        } elseif ($model instanceof EnumModel) {
            return 'enum';
        }
        return 'interface';
    }
}
