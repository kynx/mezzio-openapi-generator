<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function preg_replace;
use function str_replace;

#[CoversNothing]
final class ModelGeneratorEnd2EndTest extends TestCase
{
    private const NAMESPACE = __NAMESPACE__ . '\\Asset\\Generator';
    private const ASSET_DIR = __DIR__ . '/Asset/Generator';

    private ModelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ModelGenerator();
    }

    public function testGenerateClassSimple(): void
    {
        $className = self::NAMESPACE . '\\ClassSimple';
        $expected  = $this->getAsset($className);

        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $class    = new ClassModel($className, '/components/schemas/ClassSimple', [], $property);
        $file     = $this->generator->generate($class);

        $actual = $this->getPrinter()->printFile($file);
        self::assertSame($expected, $actual);
    }

    public function testGenerateEnumSimple(): void
    {
        $className = self::NAMESPACE . '\\EnumSimple';
        $expected  = $this->getAsset($className);

        $cases = [new EnumCase('First', 'first'), new EnumCase('Second', 'second')];
        $enum  = new EnumModel($className, '/components/schemas/EnumSimple', ...$cases);
        $file  = $this->generator->generate($enum);

        $actual = $this->getPrinter()->printFile($file);
        self::assertSame($expected, $actual);
    }

    public function testGenerateInterfaceSimple(): void
    {
        $className = self::NAMESPACE . '\\InterfaceSimple';
        $expected  = $this->getAsset($className);

        $property  = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $interface = new InterfaceModel($className, '/components/schemas/InterfaceSimple', $property);
        $file      = $this->generator->generate($interface);

        $actual = $this->getPrinter()->printFile($file);
        self::assertSame($expected, $actual);
    }

    public function getAsset(string $className): string
    {
        $namespace = str_replace('\\', '\\\\', self::NAMESPACE);
        $partName  = (string) preg_replace('/^' . $namespace . '/', '', $className);
        $filePath  = self::ASSET_DIR . str_replace('\\', '/', $partName) . '.php';
        return (string) file_get_contents($filePath);
    }

    public function getPrinter(): Printer
    {
        $printer              = new Printer();
        $printer->indentation = "    ";
        return $printer;
    }
}
