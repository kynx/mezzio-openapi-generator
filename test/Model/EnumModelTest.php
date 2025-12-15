<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnumModel::class)]
#[UsesClass(EnumCase::class)]
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

    #[DataProvider('matchesProvider')]
    public function testMatches(EnumModel|ClassModel|InterfaceModel $test, bool $expected): void
    {
        $enumModel = new EnumModel('\\A', '/A', new EnumCase('A', 'a'));
        $actual    = $enumModel->matches($test);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: EnumModel|ClassModel|InterfaceModel, 1: bool}>
     */
    public static function matchesProvider(): array
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
