<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\EnumCase
 */
final class EnumCaseTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $name  = 'Name';
        $value = 'value';
        $case  = new EnumCase($name, $value);
        self::assertSame($name, $case->getName());
        self::assertSame($value, $case->getValue());
    }
}
