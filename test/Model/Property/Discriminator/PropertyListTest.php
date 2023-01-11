<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList
 */
final class PropertyListTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $classMap      = ['\\Foo' => ['a', 'b'], '\\Bar' => ['c', 'd']];
        $discriminator = new PropertyList($classMap);
        self::assertSame($classMap, $discriminator->getClassMap());
    }
}
