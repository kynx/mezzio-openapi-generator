<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\SimplePropertyTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 *
 * @template T of PropertyType|ClassString
 */
final class SimpleProperty extends AbstractProperty
{
    /**
     * @param T $type
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $originalName,
        protected readonly PropertyMetadata $metadata,
        private readonly PropertyType|ClassString $type
    ) {
    }

    /**
     * @return T
     */
    public function getType(): PropertyType|ClassString
    {
        return $this->type;
    }
}
