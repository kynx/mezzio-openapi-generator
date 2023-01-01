<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator;
use Nette\PhpGenerator\EnumCase as NetteEnumCase;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\EnumCase
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\EnumModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator
 */
final class EnumGeneratorTest extends TestCase
{
    private EnumGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new EnumGenerator();
    }

    public function testAddEnumAddsCases(): void
    {
        $expected = [
            'Foo' => (new NetteEnumCase('Foo'))->setValue('foo'),
            'Bar' => (new NetteEnumCase('Bar'))->setValue('bar'),
        ];
        $cases    = array_map(
            fn (NetteEnumCase $case): EnumCase => new EnumCase($case->getName(), (string) $case->getValue()),
            $expected
        );
        $enum     = new EnumModel('\\Foo', '/Foo', ...$cases);

        $added = $this->generator->addEnum(new PhpNamespace('A'), $enum);
        self::assertSame('Foo', $added->getName());
        self::assertEquals($expected, $added->getCases());
    }
}
