<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Namer;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 */
final class NamespacedNamerTest extends TestCase
{
    public function testKeyByUniqueNameReturnsUnique(): void
    {
        $labeler  = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $namer    = new NamespacedNamer(__NAMESPACE__, $labeler);
        $names    = ['foo', 'Foo', 'class', 'foo bar', 'Foo Bar', 'class foo'];
        $expected = [
            __NAMESPACE__ . '\\Foo1'            => 'foo',
            __NAMESPACE__ . '\\Foo2'            => 'Foo',
            __NAMESPACE__ . '\\ClassModel'      => 'class',
            __NAMESPACE__ . '\\Foo\\Bar1'       => 'foo bar',
            __NAMESPACE__ . '\\Foo\\Bar2'       => 'Foo Bar',
            __NAMESPACE__ . '\\ClassModel\\Foo' => 'class foo',
        ];

        $actual = $namer->keyByUniqueName($names);
        self::assertSame($expected, $actual);
    }
}
