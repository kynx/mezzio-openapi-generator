<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use DateInterval;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateIntervalMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateIntervalMapper::class)]
final class DateIntervalMapperTest extends TestCase
{
    #[DataProvider('canMapProvider')]
    public function testCanMap(string $type, string|null $format, bool $expected): void
    {
        $mapper = new DateIntervalMapper();
        $actual = $mapper->canMap($type, $format);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: bool}>
     */
    public static function canMapProvider(): array
    {
        return [
            'int_null'        => ['int', null, false],
            'int_duration'    => ['int', 'duration', false],
            'string_date'     => ['string', 'date', false],
            'string_duration' => ['string', 'duration', true],
        ];
    }

    public function testGetClassString(): void
    {
        $mapper = new DateIntervalMapper();
        $actual = $mapper->getClassString('string', 'duration');
        self::assertSame(DateInterval::class, $actual);
    }
}
