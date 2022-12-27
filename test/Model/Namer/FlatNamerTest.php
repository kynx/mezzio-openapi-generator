<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Namer;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\FlatNamer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Namer\FlatNamer
 */
final class FlatNamerTest extends TestCase
{
    public function testKeyByUniqueNameReturnsUnique(): void
    {
        $labeler  = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $namer    = new FlatNamer(__NAMESPACE__, $labeler);
        $names    = ['foo', 'Foo', 'class'];
        $expected = [
            __NAMESPACE__ . '\\Foo1'       => 'foo',
            __NAMESPACE__ . '\\Foo2'       => 'Foo',
            __NAMESPACE__ . '\\ClassModel' => 'class',
        ];

        $actual = $namer->keyByUniqueName($names);
        self::assertSame($expected, $actual);
    }
}
