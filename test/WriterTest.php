<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Writer;
use Kynx\Mezzio\OpenApiGenerator\WriterException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;

use function copy;
use function current;
use function file_exists;
use function file_get_contents;
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

        $this->dir = (string) tempnam(sys_get_temp_dir(), 'phpunit_writer');
        unlink($this->dir) && mkdir($this->dir);

        $this->writer = new Writer(self::BASE_NAMESPACE, $this->dir);
    }

    public function testWriteUnwritableDirectoryThrowsException(): void
    {
        rmdir($this->dir);
        touch($this->dir);
        $directory = $this->dir . '/Bar';

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\Bar\\Baz');

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

    public function testWriteBrokenExistingOverwrites(): void
    {
        $expected = $this->dir . '/Broken.php';
        copy(__DIR__ . '/Asset/Broken.php', $expected);

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\Broken');

        $this->writer->write($file);
        self::assertFileExists($expected);

        $written = PhpFile::fromCode((string) file_get_contents($expected));
        $class = current($written->getClasses());
        self::assertNotFalse($class);
        self::assertSame('Broken', $class->getName());
    }

    public function testWriteNoClassOverwrites(): void
    {
        $expected = $this->dir . '/NoClass.php';
        copy(__DIR__ . '/Asset/NoClass.php', $expected);

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\NoClass');

        $this->writer->write($file);
        self::assertFileExists($expected);

        $written = PhpFile::fromCode((string) file_get_contents($expected));
        $class = current($written->getClasses());
        self::assertNotFalse($class);
        self::assertSame('NoClass', $class->getName());
    }

    public function testWriteNoOverwriteDoesNotWrite(): void
    {
        $expected = $this->dir . '/DoNotWrite.php';
        copy(__DIR__ . '/Asset/DoNotWrite.php', $expected);

        $file = new PhpFile();
        $file->addClass(self::BASE_NAMESPACE . '\\DoNotWrite');

        $this->writer->write($file);
        self::assertFileExists($expected);

        $written = PhpFile::fromCode((string) file_get_contents($expected));
        $class   = current($written->getClasses());
        self::assertInstanceOf(ClassType::class, $class);
        self::assertTrue($class->hasMethod('custom'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->unlink($this->dir);
    }

    private function unlink(string $dir): void
    {
        if (is_dir($dir)) {
            foreach ((array) glob($dir . '/*') as $file) {
                $this->unlink((string) $file);
            }
            rmdir($dir);
        } elseif (file_exists($dir)) {
            unlink($dir);
        }
    }
}
