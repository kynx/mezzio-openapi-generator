<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-require-extends TestCase
 */
trait GeneratorTrait
{
    protected function getNamespace(PhpFile $file, string $namespace): PhpNamespace
    {
        $namespaces = $file->getNamespaces();
        self::assertCount(1, $namespaces);
        self::assertArrayHasKey($namespace, $namespaces);
        return $namespaces[$namespace];
    }

    protected function getClass(PhpNamespace $namespace, string $className): ClassType
    {
        $classes = $namespace->getClasses();
        self::assertCount(1, $classes);
        self::assertArrayHasKey($className, $classes);
        $class = $classes[$className];
        self::assertInstanceOf(ClassType::class, $class);
        return $class;
    }

    protected function getMethod(ClassType $class, string $method): Method
    {
        self::assertTrue($class->hasMethod($method));
        return $class->getMethod($method);
    }

    protected function getConstant(ClassType $class, string $name): Constant
    {
        $constants = $class->getConstants();
        self::assertArrayHasKey($name, $constants);
        return $constants[$name];
    }
}
