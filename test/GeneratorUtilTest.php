<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use PHPUnit\Framework\TestCase;

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
}
