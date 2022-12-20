<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty
 */
final class ArrayPropertyTest extends TestCase
{
    public function testGettersReturnValues(): void
    {
        $isList     = true;
        $memberType = PropertyType::Integer;
        $property   = new ArrayProperty('$foo', 'foo', new PropertyMetadata(), $isList, $memberType);

        self::assertSame($isList, $property->isList());
        self::assertSame($memberType, $property->getMemberType());
    }
}
