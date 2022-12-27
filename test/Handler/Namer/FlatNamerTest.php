<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Namer;

use cebe\openapi\spec\Operation;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Handler\Namer\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;

use function array_combine;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\Namer\FlatNamer
 */
final class FlatNamerTest extends TestCase
{
    private FlatNamer $namer;

    protected function setUp(): void
    {
        parent::setUp();

        $labeler     = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $this->namer = new FlatNamer(__NAMESPACE__, $labeler);
    }

    public function testKeyByUniqueNameUsesOperationId(): void
    {
        $route    = new OpenApiRoute('/bar', 'get', new Operation(['operationId' => 'getFoo']));
        $expected = [__NAMESPACE__ . '\\GetFoo' => $route];

        $actual = $this->namer->keyByUniqueName([$route]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameNormalizesOperationId(): void
    {
        $route    = new OpenApiRoute('/bar', 'get', new Operation(['operationId' => '~ref']));
        $expected = [__NAMESPACE__ . '\\TildeRef' => $route];

        $actual = $this->namer->keyByUniqueName([$route]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameUsesPathAndMethod(): void
    {
        $route    = new OpenApiRoute('/foo/bar', 'get', new Operation(['operationId' => '']));
        $expected = [__NAMESPACE__ . '\\FooBarGet' => $route];

        $actual = $this->namer->keyByUniqueName([$route]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameStripsParameterMarkers(): void
    {
        $route    = new OpenApiRoute('/foo/{bar}', 'get', new Operation(['operationId' => '']));
        $expected = [__NAMESPACE__ . '\\FooBarGet' => $route];

        $actual = $this->namer->keyByUniqueName([$route]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameNormalizesPathAndMethod(): void
    {
        $route    = new OpenApiRoute('/foo/~bar', 'get', new Operation(['operationId' => '']));
        $expected = [__NAMESPACE__ . '\\FooTildeBarGet' => $route];

        $actual = $this->namer->keyByUniqueName([$route]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameCreatesUnique(): void
    {
        $routes   = [
            new OpenApiRoute('/foobar', 'get', new Operation(['operationId' => 'foobar'])),
            new OpenApiRoute('/foo/bar', 'get', new Operation(['operationId' => 'fooBar'])),
        ];
        $labels   = [__NAMESPACE__ . '\\Foobar1', __NAMESPACE__ . '\\FooBar2'];
        $expected = array_combine($labels, $routes);

        $actual = $this->namer->keyByUniqueName($routes);

        self::assertSame($expected, $actual);
    }
}
