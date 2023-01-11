<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue
 */
final class PropertyValueTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $propertyName  = 'foo';
        $valueMap      = ['bar' => '\\Bar', 'baz' => '\\Baz'];
        $discriminator = new PropertyValue($propertyName, $valueMap);
        self::assertSame($propertyName, $discriminator->getKey());
        self::assertSame($valueMap, $discriminator->getValueMap());
    }
}
