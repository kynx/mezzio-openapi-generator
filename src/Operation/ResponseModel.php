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
final class ResponseModel
{
    /**
     * @param list<ResponseHeader> $headers
     */
    public function __construct(
        private readonly string $status,
        private readonly string $description,
        private readonly string|null $mimeType,
        private readonly PropertyInterface|null $type,
        private readonly array $headers = []
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMimeType(): string|null
    {
        return $this->mimeType;
    }

    public function getType(): PropertyInterface|null
    {
        return $this->type;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
