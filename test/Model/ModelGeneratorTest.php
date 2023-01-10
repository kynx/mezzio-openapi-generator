<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use PHPUnit\Framework\TestCase;

use function current;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\EnumModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator
 */
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
        $expected = new Attribute(OpenApiSchema::class, [$pointer]);
        $model    = new ClassModel('\\A\\A', $pointer, []);

        $file = $this->generator->generate($model);

        self::assertTrue($file->hasStrictTypes());

        $namespaces = $file->getNamespaces();
        self::assertCount(1, $namespaces);
        $namespace = current($namespaces);
        self::assertSame('A', $namespace->getName());

        $uses = $namespace->getUses();
        self::assertSame(['OpenApiSchema' => OpenApiSchema::class], $uses);

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
