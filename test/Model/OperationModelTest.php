<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\OperationModel
 */
final class OperationModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $className      = '\\Foo';
        $jsonPointer    = '/components/schemas/Foo';
        $properties     = [new SimpleProperty('$foo', 'foo', new PropertyMetadata(), PropertyType::String)];
        $operationModel = new OperationModel($className, $jsonPointer, ...$properties);
        self::assertSame($className, $operationModel->getClassName());
        self::assertSame($jsonPointer, $operationModel->getJsonPointer());
        self::assertSame($properties, $operationModel->getProperties());
    }

    public function testGetImplementsReturnsEmpty(): void
    {
        $operationModel = new OperationModel('\\A', 'A');

        $actual = $operationModel->getImplements();
        self::assertEmpty($actual);
    }
}
