<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\FileSystemLocator;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
use KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Invalid\BadOpenApiOperation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\FileSystemLocator
 */
final class FileSystemLocatorTest extends TestCase
{
    private const ASSET_DIR       = __DIR__ . '/Asset/Valid';
    private const ASSET_NAMESPACE = __NAMESPACE__ . '\\Asset\\Valid';

    public function testCreateInvalidDirThrowsException(): void
    {
        $path    = __DIR__ . '/nonexistent';
        $locator = new FileSystemLocator(__NAMESPACE__ . '\\Asset', __DIR__ . '/nonexistent');

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("'$path' is not a valid path");
        $locator->create();
    }

    public function testCreateReturnsCollection(): void
    {
        $expected = [
            new HandlerClass(
                self::ASSET_NAMESPACE . '\\Handler',
                new OpenApiOperation('myId', '/my-path', 'post')
            ),
            new HandlerClass(
                self::ASSET_NAMESPACE . '\\Subdir\\SubdirHandler',
                new OpenApiOperation('my-subId', '/subdir/path', 'get')
            ),
        ];

        $locator = new FileSystemLocator(self::ASSET_NAMESPACE, self::ASSET_DIR);

        $created = $locator->create();

        foreach ($created as $i => $actual) {
            self::assertEquals($expected[$i], $actual);
        }
    }

    public function testCreateInvalidOpenApiOperationThrowsException(): void
    {
        $locator = new FileSystemLocator(__NAMESPACE__ . '\\Asset\\Invalid', __DIR__ . '/Asset/Invalid');
        $class   = BadOpenApiOperation::class;

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("Invalid OpenApiOperation attribute for class '$class'");
        $locator->create();
    }
}
