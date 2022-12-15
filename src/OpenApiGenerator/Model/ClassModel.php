<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

/**
 * @internal
 */
final class ClassModel
{
    /** @var array<string, Property> */
    private readonly array $properties;

    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        private readonly array $implements,
        Property ...$properties
    ) {
        $this->properties = $properties;
    }

    public function matches(ClassModel|EnumModel|InterfaceModel $toMatch): bool
    {
        if ($toMatch::class !== self::class) {
            return false;
        }
        return $this->className === $toMatch->getClassName();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * @return array<string, Property>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
