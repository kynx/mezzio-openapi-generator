<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

/**
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ResponseHeader
{
    public function __construct(
        private readonly string $name,
        private readonly string|null $template,
        private readonly string|null $mimeType,
        private readonly PropertyInterface $type
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTemplate(): string|null
    {
        return $this->template;
    }

    public function getMimeType(): string|null
    {
        return $this->mimeType;
    }

    public function getType(): PropertyInterface
    {
        return $this->type;
    }
}
