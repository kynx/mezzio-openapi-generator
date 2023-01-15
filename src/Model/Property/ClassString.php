<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

final class ClassString
{
    public function __construct(private readonly string $classString, private readonly bool $isEnum = false)
    {
    }

    public function getClassString(): string
    {
        return $this->classString;
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }
}
