<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiModel;
use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function assert;
use function current;

#[CoversClass(ModelGenerator::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(ClassModel::class)]
#[UsesClass(EnumModel::class)]
#[UsesClass(AbstractGenerator::class)]
#[UsesClass(ClassGenerator::class)]
#[UsesClass(EnumGenerator::class)]
#[UsesClass(InterfaceGenerator::class)]
#[UsesClass(InterfaceModel::class)]
final class ModelGeneratorTest extends TestCase
{
    private ModelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ModelGenerator();
    }

    public function testGenerateAddsNamespaceAndAttribute(): void
    {
        $pointer  = '/A';
        $expected = new Attribute(OpenApiModel::class, [$pointer]);
        $model    = new ClassModel('\\A\\A', $pointer, []);

        $file = $this->generator->generate($model);

        self::assertTrue($file->hasStrictTypes());

        $namespaces = $file->getNamespaces();
        self::assertCount(1, $namespaces);
        $namespace = current($namespaces);
        assert($namespace instanceof PhpNamespace);
        self::assertSame('A', $namespace->getName());

        $uses = $namespace->getUses();
        self::assertSame(['OpenApiModel' => OpenApiModel::class], $uses);

        $classes = $file->getClasses();
        self::assertCount(1, $classes);
        $class = current($classes);
        self::assertInstanceOf(ClassType::class, $class);

        self::assertEquals([$expected], $class->getAttributes());
    }

    public function testGenerateAddsClass(): void
    {
        $model = new ClassModel('\\A\\A', '/A', []);
        $file  = $this->generator->generate($model);

        $classes = $file->getClasses();
        $class   = current($classes);
        self::assertInstanceOf(ClassType::class, $class);
        self::assertSame('A', $class->getName());
    }

    public function testGenerateAddsEnum(): void
    {
        $model = new EnumModel('\\A\\A', '/A');
        $file  = $this->generator->generate($model);

        $classes = $file->getClasses();
        $class   = current($classes);
        self::assertInstanceOf(EnumType::class, $class);
        self::assertSame('A', $class->getName());
    }

    public function testGenerateAddsInterface(): void
    {
        $model = new InterfaceModel('\\A\\A', '/A');
        $file  = $this->generator->generate($model);

        $classes = $file->getClasses();
        $class   = current($classes);
        self::assertInstanceOf(InterfaceType::class, $class);
        self::assertSame('A', $class->getName());
    }
}
