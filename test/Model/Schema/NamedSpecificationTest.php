<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 */
final class NamedSpecificationTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $name   = 'Foo';
        $schema = new Schema([]);
        $model  = new NamedSpecification($name, $schema);
        self::assertSame($name, $model->getName());
        self::assertSame($schema, $model->getSpecification());
    }

    public function testGetJsonPointerReturnsEmptyString(): void
    {
        $model = new NamedSpecification('Foo', new Schema([]));
        self::assertSame('', $model->getJsonPointer());
    }

    public function testGetJsonPointerReturnsPointer(): void
    {
        $expected = '/components/schemas/Foo';
        $schema   = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($expected));
        $model = new NamedSpecification('Foo', $schema);
        self::assertSame($expected, $model->getJsonPointer());
    }
}
