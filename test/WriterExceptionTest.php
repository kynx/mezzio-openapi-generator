<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\WriterException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\WriterException
 */
final class WriterExceptionTest extends TestCase
{
    public function testCannotCreateDirectory(): void
    {
        $expected = new WriterException("Cannot create directory '/foo'");
        $actual   = WriterException::cannotCreateDirectory('/foo');
        self::assertEquals($expected, $actual);
    }

    public function testCannotWriteFile(): void
    {
        $expected = new WriterException("Cannot write file '/foo'");
        $actual   = WriterException::cannotWriteFile('/foo');
        self::assertEquals($expected, $actual);
    }
}
