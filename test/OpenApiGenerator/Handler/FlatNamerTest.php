<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer;
use PHPUnit\Framework\TestCase;

use function array_combine;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer
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
        $operation = new OpenApiOperation('getFoo', '/bar', 'get');
        $expected  = [__NAMESPACE__ . '\\GetFoo' => $operation];

        $actual = $this->namer->keyByUniqueName([$operation]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameNormalizesOperationId(): void
    {
        $operation = new OpenApiOperation('~ref', '/bar', 'get');
        $expected  = [__NAMESPACE__ . '\\TildeRef' => $operation];

        $actual = $this->namer->keyByUniqueName([$operation]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameUsesPathAndMethod(): void
    {
        $operation = new OpenApiOperation(null, '/foo/bar', 'get');
        $expected  = [__NAMESPACE__ . '\\FooBarGet' => $operation];

        $actual = $this->namer->keyByUniqueName([$operation]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameStripsParameterMarkers(): void
    {
        $operation = new OpenApiOperation(null, '/foo/{bar}', 'get');
        $expected  = [__NAMESPACE__ . '\\FooBarGet' => $operation];

        $actual = $this->namer->keyByUniqueName([$operation]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameNormalizesPathAndMethod(): void
    {
        $operation = new OpenApiOperation(null, '/foo/~bar', 'get');
        $expected  = [__NAMESPACE__ . '\\FooTildeBarGet' => $operation];

        $actual = $this->namer->keyByUniqueName([$operation]);

        self::assertSame($expected, $actual);
    }

    public function testKeyByUniqueNameCreatesUnique(): void
    {
        $operations = [
            new OpenApiOperation('foobar', '/foobar', 'get'),
            new OpenApiOperation('fooBar', '/foo/bar', 'get'),
        ];
        $labels     = [__NAMESPACE__ . '\\Foobar1', __NAMESPACE__ . '\\FooBar2'];
        $expected   = array_combine($labels, $operations);

        $actual = $this->namer->keyByUniqueName($operations);

        self::assertSame($expected, $actual);
    }
}
