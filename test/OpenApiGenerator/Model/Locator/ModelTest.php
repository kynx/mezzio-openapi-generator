<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model
 */
final class ModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $name = 'Foo';
        $schema = new Schema([]);
        $model = new Model($name, $schema);
        self::assertSame($name, $model->getName());
        self::assertSame($schema, $model->getSchema());
    }

    public function testGetJsonPointerReturnsEmptyString(): void
    {
        $model = new Model('Foo', new Schema([]));
        self::assertSame('', $model->getJsonPointer());
    }

    public function testGetJsonPointerReturnsPointer(): void
    {
        $expected = '/components/schemas/Foo';
        $schema = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($expected));
        $model = new Model('Foo', $schema);
        self::assertSame($expected, $model->getJsonPointer());
    }
}
