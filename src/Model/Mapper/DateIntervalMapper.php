<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use DateInterval;

/**
 * @psalm-immutable
 */
final class DateIntervalMapper implements TypeMapperInterface
{
    public function canMap(string $type, ?string $format): bool
    {
        return $type === 'string' && $format === 'duration';
    }

    public function getClassString(string $type, ?string $format): string
    {
        return DateInterval::class;
    }
}
