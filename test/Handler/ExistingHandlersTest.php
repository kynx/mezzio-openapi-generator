<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Handler\ExistingHandlers;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerException;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Invalid\BadOpenApiOperation;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\ExistingHandlers
 */
final class ExistingHandlersTest extends TestCase
{
    private const ASSET_DIR       = __DIR__ . '/Asset/Valid';
    private const ASSET_NAMESPACE = __NAMESPACE__ . '\\Asset\\Valid';

    public function testCreateInvalidDirThrowsException(): void
    {
        $path    = __DIR__ . '/nonexistent';
        $locator = new ExistingHandlers(__NAMESPACE__ . '\\Asset', __DIR__ . '/nonexistent');

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("'$path' is not a valid path");
        $locator->updateClassNames(new HandlerCollection());
    }

    public function testCreateReturnsCollection(): void
    {
        $this->markTestSkipped("Awaiting refactor");
        $expected   = [
            new HandlerClass(
                self::ASSET_NAMESPACE . '\\Handler',
                new OpenApiRoute('/my-path', 'post', new Operation(['operationId' => 'myId']))
            ),
            new HandlerClass(
                self::ASSET_NAMESPACE . '\\Subdir\\SubdirHandler',
                new OpenApiRoute('/subdir/path', 'get', new Operation(['operationId' => 'my-subId']))
            ),
            new HandlerClass(
                self::ASSET_NAMESPACE . '\\NewHandler',
                new OpenApiRoute('/another/path', 'get', new Operation([]))
            ),
        ];
        $collection = new HandlerCollection();
        foreach ($expected as $i => $handler) {
            $className = $i < 2 ? self::ASSET_NAMESPACE . '\\Fpp' . $i : $handler->getClassName();
            $collection->add(new HandlerClass($className, $handler->getRoute()));
        }

        $locator = new ExistingHandlers(self::ASSET_NAMESPACE, self::ASSET_DIR);

        $created = $locator->updateClassNames($collection);
        self::assertSame(count($expected), count($created));
        foreach ($created as $i => $actual) {
            self::assertEquals($expected[$i], $actual);
        }
    }

    public function testCreateInvalidOpenApiOperationThrowsException(): void
    {
        $this->markTestSkipped("Awaiting refactor");
        $locator = new ExistingHandlers(__NAMESPACE__ . '\\Asset\\Invalid', __DIR__ . '/Asset/Invalid');
        $class   = BadOpenApiOperation::class;

        self::expectException(HandlerException::class);
        self::expectExceptionMessage("Invalid OpenApiOperation attribute for class '$class'");
        $locator->updateClassNames(new HandlerCollection());
    }
}
