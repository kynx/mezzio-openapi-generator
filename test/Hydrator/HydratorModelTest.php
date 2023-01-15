<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorModel
 */
final class HydratorModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className  = '\\FooHydrator';
        $classModel = new ClassModel('\\Foo', '/components/schemas/Foo', []);
        $actual     = new HydratorModel($className, $classModel);
        self::assertSame($className, $actual->getClassName());
        self::assertSame($classModel, $actual->getModel());
    }
}
