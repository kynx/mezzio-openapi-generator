<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\Util;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\Util
 */
final class UtilTest extends TestCase
{
    /**
     * @dataProvider pathProvider
     */
    public function testEncodePath(string $path, string $expected): void
    {
        $actual = Util::encodePath($path);
        self::assertSame($expected, $actual);
    }

    public function pathProvider(): array
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
