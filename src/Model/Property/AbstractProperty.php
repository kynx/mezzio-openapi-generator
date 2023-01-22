<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\AbstractPropertyTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
abstract class AbstractProperty implements PropertyInterface
{
    protected readonly string $name;
    protected readonly string $originalName;
    protected readonly PropertyMetadata $metadata;

    public function getName(): string
    {
        return $this->name;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMetadata(): PropertyMetadata
    {
        return $this->metadata;
    }

    protected function getClassString(ClassString|PropertyType $type): string|null
    {
        return $type instanceof ClassString ? $type->getClassString() : null;
    }

    protected function getTypeString(ClassString|PropertyType $type): string
    {
        return $type instanceof PropertyType ? $type->toPhpType() : $type->getClassString();
    }

    protected function getShortType(ClassString|PropertyType $type): string
    {
        return $type instanceof PropertyType
            ? $type->toPhpType()
            : GeneratorUtil::getClassName($type->getClassString());
    }
}
