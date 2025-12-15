<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InterfaceModel::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(SimpleProperty::class)]
final class InterfaceModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className      = '\\Foo';
        $jsonPointer    = '/components/schemas/Foo';
        $properties     = [new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String)];
        $interfaceModel = new InterfaceModel($className, $jsonPointer, ...$properties);
        self::assertSame($className, $interfaceModel->getClassName());
        self::assertSame($jsonPointer, $interfaceModel->getJsonPointer());
        self::assertSame($properties, $interfaceModel->getProperties());
    }
}
