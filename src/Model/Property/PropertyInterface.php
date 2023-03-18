<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

/**
 * @internal
 *
 * @psalm-immutable
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

    public function getDocBlockType(bool $forUnion = false): string|null;

    /**
     * @return list<ClassString|PropertyType>
     */
    public function getTypes(): array;
}
