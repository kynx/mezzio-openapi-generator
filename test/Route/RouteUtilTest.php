<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil
 */
final class RouteUtilTest extends TestCase
{
    /**
     * @dataProvider pathProvider
     */
    public function testEncodePath(string $path, string $expected): void
    {
        $actual = RouteUtil::encodePath($path);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'none'                => ['/a/b', '/a/b'],
            'whitespace'          => [' /a/b ', '/a/b'],
            'internal_whitespace' => ['/a/ b', '/a/%20b'],
            'non_ascii'           => ['/hüsker/dü', '/h%C3%BCsker/d%C3%BC'],
            'param'               => ['/a/{b}', '/a/{b}'],
        ];
    }
}
