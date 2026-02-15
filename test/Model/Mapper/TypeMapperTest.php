<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeMapper::class)]
final class TypeMapperTest extends TestCase
{
    public function testMapTypeArrayThrowsException(): void
    {
        $spec       = ['type' => ['string', 'number']];
        $schema     = new Schema($spec);
        $typeMapper = new TypeMapper(self::createStub(TypeMapperInterface::class));

        self::expectException(ModelException::class);
        self::expectExceptionMessage('OpenAPI 3.1 type arrays are not supported yet');
        $typeMapper->map($schema);
    }

    public function testMapReturnsClassString(): void
    {
        $expected = new ClassString(self::class);
        $spec     = ['type' => 'foo'];
        $schema   = new Schema($spec);

        $mapper = $this->createMock(TypeMapperInterface::class);
        $mapper->expects(self::once())
            ->method('canMap')
            ->with('foo', null)
            ->willReturn(true);
        $mapper->expects(self::once())
            ->method('getClassString')
            ->with('foo', null)
            ->willReturn($expected->getClassString());

        $typeMapper = new TypeMapper($mapper);
        $actual     = $typeMapper->map($schema);
        self::assertEquals($expected, $actual);
    }

    public function testMapCannotMapReturnPropertyType(): void
    {
        $expected = PropertyType::String;
        $spec     = ['type' => 'string', 'format' => 'datetime'];
        $schema   = new Schema($spec);

        $mapper = $this->createMock(TypeMapperInterface::class);
        $mapper->expects(self::once())
            ->method('canMap')
            ->with('string', 'datetime')
            ->willReturn(false);

        $typeMapper = new TypeMapper($mapper);
        $actual     = $typeMapper->map($schema);
        self::assertEquals($expected, $actual);
    }
}
