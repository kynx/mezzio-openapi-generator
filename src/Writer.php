<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApi\Attribute\NotOverwritable;
use Nette\InvalidStateException;
use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;

use function array_map;
use function assert;
use function current;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_dir;
use function is_file;
use function ltrim;
use function mkdir;
use function preg_replace;
use function rtrim;
use function str_replace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\WriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class Writer implements WriterInterface
{
    public function __construct(
        private readonly string $baseNamespace,
        private readonly string $baseDir,
        private readonly Printer $printer = new PsrPrinter()
    ) {
    }

    public function write(PhpFile $file): void
    {
        $directory = $this->getDirectory($file);
        if (! (is_dir($directory) || @mkdir($directory, 0777, true))) {
            throw WriterException::cannotCreateDirectory($directory);
        }

        $path = $directory . '/' . $this->getFileName($file);
        if (! $this->canOverWrite($path)) {
            return;
        }

        if (@file_put_contents($path, $this->printer->printFile($file)) === false) {
            throw WriterException::cannotWriteFile($path);
        }
    }

    private function canOverWrite(string $path): bool
    {
        if (! (file_exists($path) && is_file($path))) {
            return true;
        }

        try {
            $file = PhpFile::fromCode(@file_get_contents($path));
        } catch (InvalidStateException) {
            return true;
        }

        $class = current($file->getClasses());
        if (! $class instanceof ClassLike) {
            return true;
        }

        $attributes = array_map(fn (Attribute $attribute): string => $attribute->getName(), $class->getAttributes());
        return ! in_array(NotOverwritable::class, $attributes);
    }

    private function getDirectory(PhpFile $file): string
    {
        $namespace = current($file->getNamespaces());
        assert($namespace instanceof PhpNamespace);

        $regExp       = '/^' . str_replace('\\', '\\\\', $this->normalize($this->baseNamespace)) . '/';
        $subNamespace = preg_replace($regExp, '', $this->normalize($namespace->getName()));

        return rtrim($this->baseDir . '/' . str_replace('\\', '/', $this->normalize($subNamespace)), '/');
    }

    private function getFileName(PhpFile $file): string
    {
        $class = current($file->getClasses());
        assert($class instanceof ClassLike);
        $name = $class->getName();
        assert($name !== null);

        return $name . '.php';
    }

    private function normalize(string $namespace): string
    {
        return ltrim($namespace, '\\');
    }
}
