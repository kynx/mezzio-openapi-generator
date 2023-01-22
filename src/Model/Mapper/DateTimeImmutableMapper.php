<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final class DateTimeImmutableMapper implements TypeMapperInterface
{
    public function canMap(string $type, ?string $format): bool
    {
        return $type === 'string' && $format === 'date';
    }

    public function getClassString(string $type, ?string $format): string
    {
        return DateTimeImmutable::class;
    }
}
