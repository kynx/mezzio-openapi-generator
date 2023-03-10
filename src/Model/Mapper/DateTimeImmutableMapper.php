<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use DateTimeImmutable;

use function in_array;

/**
 * @psalm-immutable
 */
final class DateTimeImmutableMapper implements TypeMapperInterface
{
    public function canMap(string $type, ?string $format): bool
    {
        return $type === 'string' && in_array($format, ['date', 'date-time'], true);
    }

    public function getClassString(string $type, ?string $format): string
    {
        return DateTimeImmutable::class;
    }
}
