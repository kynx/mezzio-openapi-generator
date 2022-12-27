<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 */
final class ModelUtilTest extends TestCase
{
    public function testGetJsonPointerReferenceReturnsEmptyString(): void
    {
        $expected  = '';
        $reference = new Reference(['$ref' => '/foo']);
        $actual    = ModelUtil::getJsonPointer($reference);
        self::assertSame($expected, $actual);
    }

    public function testGetJsonPointerSchemaReturnsPointer(): void
    {
        $expected = '/components/schemas/Foo';
        $schema   = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($expected));
        $actual = ModelUtil::getJsonPointer($schema);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider isEnumProvider
     */
    public function testIsEnum(array $spec, bool $expected): void
    {
        $schema = new Schema($spec);
        $actual = ModelUtil::isEnum($schema);
        self::assertSame($expected, $actual);
    }

    public function isEnumProvider(): array
    {
        return [
            'enum'    => [['type' => 'string', 'enum' => ['a', 'b']], true],
            'integer' => [['type' => 'integer', 'enum' => [1, 2]], false],
            'string'  => [['type' => 'string'], false],
            'mixed'   => [['enum' => ['a', 1]], false],
            'object'  => [['type' => 'object'], false],
        ];
    }
}
