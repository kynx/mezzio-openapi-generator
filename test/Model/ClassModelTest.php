<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassModel::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(SimpleProperty::class)]
final class ClassModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className  = '\\Foo';
        $schemaName = 'Bar';
        $implements = ['\\Bar'];
        $property   = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String);
        $actual     = new ClassModel($className, $schemaName, $implements, $property);

        self::assertSame($className, $actual->getClassName());
        self::assertSame($schemaName, $actual->getJsonPointer());
        self::assertSame($implements, $actual->getImplements());
        self::assertSame([$property], $actual->getProperties());
    }
}
