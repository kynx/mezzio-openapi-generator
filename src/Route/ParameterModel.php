<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\ParameterModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ParameterModel
{
    /**
     * @param string|array<string>|null $type
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $hasContent,
        private readonly string|array|null $type = null,
        private readonly string|null $style = null,
        private readonly bool|null $explode = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasContent(): bool
    {
        return $this->hasContent;
    }

    /**
     * @return string|array<string>|null
     */
    public function getType(): string|array|null
    {
        return $this->type;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function getExplode(): ?bool
    {
        return $this->explode;
    }
}
