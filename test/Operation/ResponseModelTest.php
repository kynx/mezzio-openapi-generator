<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseHeader;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel
 */
final class ResponseModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $status      = '200';
        $description = 'Pet details';
        $mimeType    = 'application/json';
        $type        = $this->createStub(PropertyInterface::class);
        $headers     = [
            new ResponseHeader('X-Foo', null, $mimeType, $type),
        ];

        $responseModel = new ResponseModel($status, $description, $mimeType, $type, $headers);
        self::assertSame($status, $responseModel->getStatus());
        self::assertSame($description, $responseModel->getDescription());
        self::assertSame($mimeType, $responseModel->getMimeType());
        self::assertSame($type, $responseModel->getType());
        self::assertSame($headers, $responseModel->getHeaders());
    }
}
