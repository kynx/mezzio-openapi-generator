<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel
 */
final class RequestBodyModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $mimeType = 'application/json';
        $type     = new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String);

        $model = new RequestBodyModel($mimeType, $type);
        self::assertSame($mimeType, $model->getMimeType());
        self::assertSame($type, $model->getType());
    }
}
