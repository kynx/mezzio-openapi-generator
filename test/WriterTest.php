<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterException;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function glob;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function unlink;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\WriterException
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Writer
 */
final class WriterTest extends TestCase
{
    private const BASE_NAMESPACE = 'Foo';

    private string $dir;
    private Writer $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = tempnam(sys_get_temp_dir(), 'phpunit_writer');
        unlink($this->dir) && mkdir($this->dir);

        $this->writer = new Writer(self::BASE_NAMESPACE, $this->dir);
    }

    public function testWriteUnwritableDirectoryThrowsException(): void
    {
        rmdir($this->dir);
        touch($this->dir);
        $directory = $this->dir . '/Bar';

        $file = new PhpFile();
        $file->addNamespace(self::BASE_NAMESPACE . '\\Bar');

        self::expectException(WriterException::class);
        self::expectExceptionMessage("Cannot create directory '$directory'");
        $this->writer->write($file);
    }

    public function testWriteUnwritableFileThrowsException(): void
    {
        $path = $this->dir . '/Bar.php';
        mkdir($path);

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\Bar');

        self::expectException(WriterException::class);
        self::expectExceptionMessage("Cannot write file '$path'");
        $this->writer->write($file);
    }

    public function testWriteCreatesSubdirectory(): void
    {
        $expected = $this->dir . '/Bar/Baz/Zog.php';

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\Bar\Baz\Zog');

        $this->writer->write($file);
        self::assertFileExists($expected);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->unlink($this->dir);
    }

    private function unlink(string $dir): void
    {
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $file) {
                $this->unlink($file);
            }
            rmdir($dir);
        } elseif (file_exists($dir)) {
            unlink($dir);
        }
    }
}
