<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer
 */
final class FlatNamerTest extends TestCase
{
    private FlatNamer $namer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->namer = new FlatNamer(__NAMESPACE__, new ClassNameNormalizer('Handler'));
    }

    public function testGetNameUsesOperationId(): void
    {
        $expected = __NAMESPACE__ . '\\GetFoo';
        $operation = new OpenApiOperation('getFoo', '/bar', 'get');

        $actual = $this->namer->getName($operation);

        self::assertSame($expected, $actual);
    }

    public function testGetNameNormalizesOperationId(): void
    {
        $expected = __NAMESPACE__ . '\\TildeRef';
        $operation = new OpenApiOperation('~ref', '/bar', 'get');

        $actual = $this->namer->getName($operation);

        self::assertSame($expected, $actual);
    }

    public function testGetNameUsesPathAndMethod(): void
    {
        $expected = __NAMESPACE__ . '\\FooBarGet';
        $operation = new OpenApiOperation(null, '/foo/bar', 'get');

        $actual = $this->namer->getName($operation);

        self::assertSame($expected, $actual);
    }

    public function testGetNameStripsParameterMarkers(): void
    {
        $expected = __NAMESPACE__ . '\\FooBarGet';
        $operation = new OpenApiOperation(null, '/foo/{bar}', 'get');

        $actual = $this->namer->getName($operation);

        self::assertSame($expected, $actual);
    }

    public function testGetNameNormalizesPathAndMethod(): void
    {
        $expected = __NAMESPACE__ . '\\FooTildeBarGet';
        $operation = new OpenApiOperation(null, '/foo/~bar', 'get');

        $actual = $this->namer->getName($operation);

        self::assertSame($expected, $actual);
    }
}
