<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Nette\PhpGenerator\Dumper;
use PHPUnit\Framework\TestCase;

use function array_map;
use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\GeneratorUtil
 */
final class GeneratorUtilTest extends TestCase
{
    /**
     * @dataProvider namespaceProvider
     */
    public function testGetNamespaceReturnsNamespace(string $fqcn, string $expected): void
    {
        $actual = GeneratorUtil::getNamespace($fqcn);
        self::assertSame($expected, $actual);
    }

    public function namespaceProvider(): array
    {
        return [
            'leading_slash'   => ['\\Api\\Model\\Foo', 'Api\\Model'],
            'fully_qualified' => ['Api\\Model\\Foo', 'Api\\Model'],
            'class_string'    => [self::class, __NAMESPACE__],
            'no_namespace'    => ['\\Foo', ''],
        ];
    }

    /**
     * @dataProvider classNameProvider
     */
    public function testGetClassNameReturnsClassName(string $fqcn, string $expected): void
    {
        $actual = GeneratorUtil::getClassName($fqcn);
        self::assertSame($expected, $actual);
    }

    public function classNameProvider(): array
    {
        return [
            'leading_slash'   => ['\\Api\\Model\\Foo', 'Foo'],
            'fully_qualified' => ['Api\\Model\\Foo', 'Foo'],
            'no_namespace'    => ['\\Foo', 'Foo'],
            'no_slash'        => ['Foo', 'Foo'],
        ];
    }

    public function testFormAsListReturnsFormatted(): void
    {
        $expected = "'first', 'second', 'third'";
        $actual   = GeneratorUtil::formatAsList(new Dumper(), ['first', 'second', 'third']);
        self::assertSame($expected, $actual);
    }

    public function testFormatAsListBreaksLines(): void
    {
        $poem     = [
            'Can a parrot',
            'Eat a carrot',
            'Standing on its head?',
            'If I did that',
            'My mom would send me',
            'Straight upstairs to bed!',
        ];
        $expected = "\n" . implode("\n", array_map(fn (string $line): string => "    '$line',", $poem)) . "\n";

        $dumper              = new Dumper();
        $dumper->indentation = '    ';

        $actual = GeneratorUtil::formatAsList($dumper, $poem);
        self::assertSame($expected, $actual);
    }
}
