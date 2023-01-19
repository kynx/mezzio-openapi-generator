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
final class RequestBodyModel
{
    public function __construct(
        private readonly string $mimeType,
        private readonly PropertyInterface $type
    ) {
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getType(): PropertyInterface
    {
        return $this->type;
    }
}
