<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\EnumCaseTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class EnumCase
{
    public function __construct(private readonly string $name, private readonly string $value)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
