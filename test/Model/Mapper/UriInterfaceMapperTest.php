<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\UriInterfaceMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

#[CoversClass(UriInterfaceMapper::class)]
final class UriInterfaceMapperTest extends TestCase
{
    #[DataProvider('canMapProvider')]
    public function testCanMap(string $type, string|null $format, bool $expected): void
    {
        $mapper = new UriInterfaceMapper();
        $actual = $mapper->canMap($type, $format);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: bool}>
     */
    public static function canMapProvider(): array
    {
        return [
            'int_null'             => ['int', null, false],
            'int_uri'              => ['int', 'uri', false],
            'string_uri-reference' => ['string', 'uri-reference', false],
            'string_uri'           => ['string', 'uri', true],
        ];
    }

    public function testGetClassString(): void
    {
        $mapper = new UriInterfaceMapper();
        $actual = $mapper->getClassString('string', 'uri');
        self::assertSame(UriInterface::class, $actual);
    }
}
