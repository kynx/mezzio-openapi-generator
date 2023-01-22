<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapper
 */
final class TypeMapperTest extends TestCase
{
    public function testMapReturnsClassString(): void
    {
        $expected = new ClassString(self::class);
        $spec     = ['type' => 'foo'];
        $schema   = new Schema($spec);

        $mapper = $this->createMock(TypeMapperInterface::class);
        $mapper->method('canMap')
            ->with('foo', null)
            ->willReturn(true);
        $mapper->method('getClassString')
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
        $mapper->method('canMap')
            ->with('string', 'datetime')
            ->willReturn(false);

        $typeMapper = new TypeMapper($mapper);
        $actual     = $typeMapper->map($schema);
        self::assertEquals($expected, $actual);
    }
}
