<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel
 */
final class HandlerModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $jsonPointer = '/paths/~1foo/get';
        $className   = 'Handler\\Foo\\GetHandler';
        $operation   = new OperationModel('Operation\\Foo\\Get\\Operation', $jsonPointer);
        $model       = new HandlerModel($jsonPointer, $className, $operation);

        self::assertSame($jsonPointer, $model->getJsonPointer());
        self::assertSame($className, $model->getClassName());
        self::assertSame($operation, $model->getOperation());
    }
}
