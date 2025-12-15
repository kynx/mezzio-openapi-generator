<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeImmutableMapper::class)]
final class DateTimeImmutableMapperTest extends TestCase
{
    #[DataProvider('canMapProvider')]
    public function testCanMap(string $type, string|null $format, bool $expected): void
    {
        $mapper = new DateTimeImmutableMapper();
        $actual = $mapper->canMap($type, $format);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: bool}>
     */
    public static function canMapProvider(): array
    {
        return [
            'int_null'         => ['int', null, false],
            'int_ddate'        => ['int', 'date', false],
            'string_duration'  => ['string', 'duration', false],
            'string_date'      => ['string', 'date', true],
            'string_date-time' => ['string', 'date-time', true],
        ];
    }

    public function testGetClassString(): void
    {
        $mapper = new DateTimeImmutableMapper();
        $actual = $mapper->getClassString('string', 'duration');
        self::assertSame(DateTimeImmutable::class, $actual);
    }
}
