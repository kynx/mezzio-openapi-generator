<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use Psr\Http\Message\UriInterface;

/**
 * @psalm-immutable
 */
final class UriInterfaceMapper implements TypeMapperInterface
{
    public function canMap(string $type, ?string $format): bool
    {
        return $type === 'string' && $format === 'uri';
    }

    public function getClassString(string $type, ?string $format): string
    {
        return UriInterface::class;
    }
}
