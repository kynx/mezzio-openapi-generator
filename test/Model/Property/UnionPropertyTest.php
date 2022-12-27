<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 */
final class UnionPropertyTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $members  = [PropertyType::Integer, '\\Foo'];
        $property = new UnionProperty('$foo', 'foo', new PropertyMetadata(), ...$members);
        self::assertSame($members, $property->getMembers());
    }
}
