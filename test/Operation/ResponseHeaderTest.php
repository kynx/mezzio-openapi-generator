<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseHeader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseHeader::class)]
final class ResponseHeaderTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $name     = 'X-Foo';
        $template = '{X-Foo*}';
        $mimeType = null;
        $type     = $this->createStub(PropertyInterface::class);

        $responseHeader = new ResponseHeader($name, $template, $mimeType, $type);
        self::assertSame($name, $responseHeader->getName());
        self::assertSame($template, $responseHeader->getTemplate());
        self::assertSame($mimeType, $responseHeader->getMimeType());
        self::assertSame($type, $responseHeader->getType());
    }
}
