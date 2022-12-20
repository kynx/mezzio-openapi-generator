<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\EnumModel
 */
final class EnumModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className   = '\\Foo';
        $jsonPointer = '/components/schemas/Foo';
        $cases       = [new EnumCase('A', 'a')];
        $enumModel   = new EnumModel($className, $jsonPointer, ...$cases);
        self::assertSame($className, $enumModel->getClassName());
        self::assertSame($jsonPointer, $enumModel->getJsonPointer());
        self::assertSame($cases, $enumModel->getCases());
    }

    /**
     * @dataProvider matchesProvider
     */
    public function testMatches(EnumModel|ClassModel|InterfaceModel $test, bool $expected): void
    {
        $enumModel = new EnumModel('\\A', '/A', new EnumCase('A', 'a'));
        $actual    = $enumModel->matches($test);
        self::assertSame($expected, $actual);
    }

    public function matchesProvider(): array
    {
        return [
            'enum'      => [new EnumModel('\\B', '/A', new EnumCase('A', 'a')), false],
            'pointer'   => [new EnumModel('\\A', '/B', new EnumCase('A', 'a')), true],
            'case'      => [new EnumModel('\\A', '/A', new EnumCase('B', 'b')), true],
            'class'     => [new ClassModel('\\A', '/A', []), false],
            'interface' => [new InterfaceModel('\\A', '/A'), false],
        ];
    }
}
