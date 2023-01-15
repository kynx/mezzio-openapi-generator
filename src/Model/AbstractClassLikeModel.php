<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

use function array_values;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
abstract class AbstractClassLikeModel
{
    /** @var list<PropertyInterface> */
    protected readonly array $properties;

    public function __construct(
        protected readonly string $className,
        protected readonly string $jsonPointer,
        PropertyInterface ...$properties
    ) {
        $this->properties = array_values($properties);
    }

    public function matches(AbstractClassLikeModel|EnumModel $toMatch): bool
    {
        if ($toMatch::class !== static::class) {
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

    /**
     * @return list<PropertyInterface>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
