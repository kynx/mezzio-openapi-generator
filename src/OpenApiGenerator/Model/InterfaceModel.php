<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

use function array_values;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\InterfaceModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class InterfaceModel
{
    /** @var list<PropertyInterface> */
    private readonly array $properties;

    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        PropertyInterface ...$properties
    ) {
        $this->properties = array_values($properties);
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

    public function getProperties(): array
    {
        return $this->properties;
    }
}
