<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use DateTimeImmutable;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper
 */
final class DateTimeImmutableMapperTest extends TestCase
{
    /**
     * @dataProvider canMapProvider
     */
    public function testCanMap(string $type, string|null $format, bool $expected): void
    {
        $mapper = new DateTimeImmutableMapper();
        $actual = $mapper->canMap($type, $format);
        self::assertSame($expected, $actual);
    }

    public function canMapProvider(): array
    {
        return [
            'int_null'        => ['int', null, false],
            'int_ddate'       => ['int', 'date', false],
            'string_duration' => ['string', 'duration', false],
            'string_date'     => ['string', 'date', true],
        ];
    }

    public function testGetClassString(): void
    {
        $mapper = new DateTimeImmutableMapper();
        $actual = $mapper->getClassString('string', 'duration');
        self::assertSame(DateTimeImmutable::class, $actual);
    }
}
