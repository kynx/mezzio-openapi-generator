<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
interface PropertyInterface
{
    public function getName(): string;

    public function getOriginalName(): string;

    public function getMetadata(): PropertyMetadata;

    /**
     * @return array<int, string>
     */
    public function getUses(): array;

    public function getPhpType(): string;

    public function getDocBlockType(): string|null;

    /**
     * @return list<ClassString|PropertyType>
     */
    public function getTypes(): array;
}
