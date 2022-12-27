<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 */
final class SimplePropertyTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $type     = PropertyType::Integer;
        $property = new SimpleProperty('$foo', 'foo', new PropertyMetadata(), $type);
        self::assertEquals($type, $property->getType());
    }
}
